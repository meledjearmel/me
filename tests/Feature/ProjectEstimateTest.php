<?php

use App\Ai\Agents\EstimateAnalyst;
use App\Ai\Agents\EstimateMatcher;
use App\Enums\EngagementType;
use App\Jobs\AnalyzeEngagement;
use App\Mail\EngagementReceivedMail;
use App\Models\Engagement;
use App\Models\EstimateSimulation;
use App\Models\EstimatorItem;
use App\Models\EstimatorRate;
use App\Models\PageVisit;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GeoLocator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Profile::factory()->create(['email' => 'owner@example.test']);
    SiteSetting::factory()->estimating()->create();
    Mail::fake();

    // 30 – 50 h + 10 – 20 h, plus 20 % de marge : 48 – 84 h, à 100 CHF/h et 30 h par semaine.
    $this->projectType = EstimatorItem::factory()->create();
    $this->feature = EstimatorItem::factory()->feature()->create();
});

/** @return array<string, mixed> */
function estimateChoices(array $overrides = []): array
{
    return [
        'project_type_id' => test()->projectType->id,
        'feature_ids' => [test()->feature->id],
        'currency' => 'CHF',
        ...$overrides,
    ];
}

test('the estimator page lists active items and currencies with an hourly rate', function () {
    EstimatorItem::factory()->inactive()->create();
    SiteSetting::current()->update(['estimator_hourly_rates' => ['CHF' => 100, 'EUR' => 90]]);

    $this->get('/fr/estimate')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/estimate')
            ->has('projectTypes', 1)
            ->where('projectTypes.0.billing', 'fixed')
            ->has('features', 1)
            ->where('currencies', ['CHF', 'EUR']));
});

test('the currency of the visitor country is chosen, with a fallback when it has no rate', function (?string $country, array $rates, string $expected) {
    SiteSetting::current()->update(['estimator_hourly_rates' => $rates]);
    $this->mock(GeoLocator::class)->shouldReceive('locate')
        ->andReturn(['country_code' => $country, 'country' => null, 'city' => null]);

    $this->get('/fr/estimate')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCurrency', $expected));
})->with([
    'Côte d’Ivoire' => ['CI', ['CHF' => 100, 'XOF' => 30000], 'XOF'],
    'Suisse' => ['CH', ['CHF' => 100, 'EUR' => 90], 'CHF'],
    'Belgique' => ['BE', ['CHF' => 100, 'EUR' => 90], 'EUR'],
    'Canada' => ['CA', ['CHF' => 100, 'USD' => 100], 'USD'],
    'Canada sans taux en USD' => ['CA', ['CHF' => 100, 'EUR' => 90], 'CHF'],
    'pays inconnu' => [null, ['EUR' => 90, 'USD' => 100], 'EUR'],
]);

test('the estimator page is hidden without a project type or an hourly rate', function () {
    SiteSetting::current()->update(['estimator_hourly_rates' => []]);
    $this->get('/fr/estimate')->assertNotFound();

    SiteSetting::current()->update(['estimator_hourly_rates' => ['CHF' => 100]]);
    $this->projectType->update(['is_active' => false]);
    $this->get('/fr/estimate')->assertNotFound();
});

test('the quote prices the hours with the risk margin at the hourly rate', function () {
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices()))
        ->assertOk()
        ->assertExactJson([
            'billing' => 'fixed',
            'currency' => 'CHF',
            'hourly_rate' => 100,
            'hours_min' => 48,
            'hours_max' => 84,
            'price_min' => 4800,
            'price_max' => 8400,
            'weeks_min' => 2,
            'weeks_max' => 3,
            'recurring' => [],
        ]);
});

test('a recurring option stays out of the project total and is priced for the chosen period without margin', function () {
    $maintenance = EstimatorItem::factory()->recurring(['monthly' => 0, 'yearly' => 10])->create();

    // Projet inchangé : 48 – 84 h. Maintenance 2 – 4 h/mois × 100 × 12 × 0,9 = 2160 – 4320, arrondis à 50.
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices([
        'feature_ids' => [$this->feature->id, $maintenance->id],
        'periods' => [$maintenance->id => 'yearly'],
    ])))
        ->assertOk()
        ->assertJsonPath('hours_max', 84)
        ->assertJsonPath('price_max', 8400)
        ->assertJsonPath('weeks_max', 3)
        ->assertJsonPath('recurring.0.period', 'yearly')
        ->assertJsonPath('recurring.0.discount_percent', 10)
        ->assertJsonPath('recurring.0.price_min', 2150)
        ->assertJsonPath('recurring.0.price_max', 4300);
});

test('a recurring option falls back to its first period when none or an unoffered one is chosen', function () {
    $maintenance = EstimatorItem::factory()->recurring(['monthly' => 0, 'yearly' => 10])->create();

    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices([
        'feature_ids' => [$maintenance->id],
        'periods' => [$maintenance->id => 'quarterly'],
    ])))
        ->assertOk()
        ->assertJsonPath('price_max', 6000)
        ->assertJsonPath('recurring.0.period', 'monthly')
        ->assertJsonPath('recurring.0.price_min', 200)
        ->assertJsonPath('recurring.0.price_max', 400);

    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['periods' => [$maintenance->id => 'weekly']])))
        ->assertJsonValidationErrors('periods.'.$maintenance->id);
});

test('a sent estimate keeps its recurring options in the label', function () {
    Queue::fake([AnalyzeEngagement::class]);
    $maintenance = EstimatorItem::factory()->recurring()->create();

    $this->post('/fr/estimate', [
        ...estimateChoices(['feature_ids' => [$maintenance->id], 'periods' => [$maintenance->id => 'monthly']]),
        'name' => 'Awa Client',
        'email' => 'awa@example.test',
    ])->assertRedirect();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->estimate['recurring'][0]['label'])->toBe('Maintenance')
        ->and($engagement->estimate_label)->toEndWith(' + Maintenance 200 – 400 CHF/mois');
});

test('a percentage option grows the project hours before the risk margin', function () {
    $multilingual = EstimatorItem::factory()->percent(20)->create();

    // 30 – 50 h × 1,2 (multilingue) × 1,2 (marge) : 44 – 72 h.
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['feature_ids' => [$multilingual->id]])))
        ->assertOk()
        ->assertJsonPath('hours_min', 44)
        ->assertJsonPath('hours_max', 72)
        ->assertJsonPath('price_max', 7200);
});

test('each line is priced at its own rate, else its rate grid, else the global rate', function () {
    $this->projectType->update(['hourly_rates' => ['CHF' => 150]]);
    $this->feature->rate()->associate(EstimatorRate::factory()->create(['hourly_rates' => ['CHF' => 120]]))->save();

    // (30 h × 150 + 10 h × 120) × 1,2 = 6840 ; (50 h × 150 + 20 h × 120) × 1,2 = 11 880.
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices()))
        ->assertOk()
        ->assertJsonPath('hourly_rate', 150)
        ->assertJsonPath('hours_min', 48)
        ->assertJsonPath('price_min', 6850)
        ->assertJsonPath('price_max', 11900);
});

test('a percentage option is priced at the rate of the project type', function () {
    $this->projectType->update(['hourly_rates' => ['CHF' => 150]]);
    $multilingual = EstimatorItem::factory()->percent(20)->create(['hourly_rates' => ['CHF' => 999]]);

    // (30 h + 20 %) × 150 × 1,2 = 6480.
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['feature_ids' => [$multilingual->id]])))
        ->assertOk()
        ->assertJsonPath('price_min', 6500);
});

test('each configuration a visitor tries is kept once a day, whatever the currency', function () {
    SiteSetting::current()->update(['estimator_hourly_rates' => ['CHF' => 100, 'EUR' => 90]]);

    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices()))->assertOk();
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['currency' => 'EUR'])))->assertOk();
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['feature_ids' => []])))->assertOk();
    $this->withHeader('User-Agent', 'Googlebot/2.1')->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['feature_ids' => []])))->assertOk();

    expect(EstimateSimulation::query()->count())->toBe(2)
        ->and(EstimateSimulation::query()->where('features', '!=', '[]')->value('currency'))->toBe('EUR');
});

test('the dashboard follows visitors from the estimator page to a simulation and a request', function () {
    foreach (['a', 'b', 'c', 'd'] as $visitor) {
        PageVisit::factory()->create(['path' => 'fr/estimate', 'visitor_hash' => $visitor]);
    }
    EstimateSimulation::factory()->create(['visitor_hash' => 'a', 'project_type' => 'Site vitrine']);
    EstimateSimulation::factory()->create(['visitor_hash' => 'a', 'project_type' => 'Boutique']);
    EstimateSimulation::factory()->create(['visitor_hash' => 'b', 'project_type' => 'Site vitrine']);
    Engagement::factory()->create(['estimate' => ['currency' => 'CHF', 'price_min' => 1, 'price_max' => 2, 'hours_min' => 1, 'hours_max' => 2, 'weeks_min' => 1, 'weeks_max' => 1, 'project_type' => 'Site vitrine', 'features' => []]]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('estimator.simulators', 2)
        ->where('estimator.simulations', 3)
        ->where('estimator.simulation_rate', 50)
        ->where('estimator.request_rate', 50)
        ->where('estimator.project_type_funnel.0', ['label' => 'Site vitrine', 'simulators' => 2, 'requests' => 1])
    );
});

test('an option reserved to other project types is refused', function () {
    $mobileApp = EstimatorItem::factory()->create(['name' => ['fr' => 'Application mobile', 'en' => 'Mobile app']]);
    $storePublishing = EstimatorItem::factory()->feature()->create(['sort_order' => 99]);
    $storePublishing->projectTypes()->attach($mobileApp);

    $this->get('/fr/estimate')->assertInertia(fn ($page) => $page
        ->where('features.1.project_type_ids', [$mobileApp->id]));

    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['feature_ids' => [$storePublishing->id]])))
        ->assertJsonValidationErrors('feature_ids');
    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices(['project_type_id' => $mobileApp->id, 'feature_ids' => [$storePublishing->id]])))
        ->assertOk();
});

test('the quote refuses an option as project type and a currency without rate', function () {
    SiteSetting::current()->update(['estimator_hourly_rates' => ['CHF' => 100]]);

    $this->getJson('/fr/estimate/quote?'.http_build_query(estimateChoices([
        'project_type_id' => $this->feature->id,
        'currency' => 'EUR',
    ])))->assertJsonValidationErrors(['project_type_id', 'currency']);
});

test('sending an estimate stores the frozen estimate and queues its AI analysis', function () {
    Queue::fake([AnalyzeEngagement::class]);

    $this->post('/fr/estimate', [
        ...estimateChoices(['currency' => 'XOF']),
        'name' => 'Awa Client',
        'email' => 'awa@example.test',
        'timeline' => 'quarter',
    ])->assertRedirect();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->type)->toBe(EngagementType::Freelance)
        ->and($engagement->subject)->toBe('Site vitrine')
        ->and($engagement->estimate['features'])->toBe(['Paiement en ligne'])
        ->and($engagement->estimate_label)->toBe('1 450 000 – 2 525 000 FCFA (forfait, 48 – 84 h) · 2 à 3 semaines');

    Mail::assertSent(EngagementReceivedMail::class, fn ($mail) => $mail->hasTo('owner@example.test'));
    Queue::assertPushed(AnalyzeEngagement::class, fn (AnalyzeEngagement $job) => $job->engagementId === $engagement->id);
});

test('an hourly project type shows hours at the hourly rate', function () {
    Queue::fake([AnalyzeEngagement::class]);
    $hourly = EstimatorItem::factory()->hourly()->create();

    $this->post('/fr/estimate', [
        ...estimateChoices(['project_type_id' => $hourly->id]),
        'name' => 'Awa Client',
        'email' => 'awa@example.test',
    ])->assertRedirect();

    expect(Engagement::query()->firstOrFail()->estimate_label)
        ->toBe('48 – 84 h × 100 CHF/h ≈ 4 800 – 8 400 CHF · 2 à 3 semaines');
});

test('the received request is analysed by the AI', function () {
    EstimateAnalyst::fake([json_encode([
        'summary' => 'Une vitrine avec paiement.',
        'risks' => ['Contenus non fournis', ''],
        'unclear_points' => ['Nombre de pages'],
        'questions' => ['Qui rédige les textes ?'],
        'hours_min' => 70,
        'hours_max' => 55,
        'billing' => 'hourly',
        'reasoning' => 'Le périmètre reste ouvert.',
    ])]);

    $this->post('/fr/estimate', [
        ...estimateChoices(),
        'name' => 'Awa Client',
        'email' => 'awa@example.test',
        'message' => 'Il faudra aussi migrer notre ancien site.',
    ])->assertRedirect();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->ai_analysis['risks'])->toBe(['Contenus non fournis'])
        ->and($engagement->ai_analysis['hours_min'])->toBe(55)
        ->and($engagement->ai_analysis['hours_max'])->toBe(70)
        ->and($engagement->ai_analysis['price_max'])->toBe(7000)
        ->and($engagement->ai_analyzed_at)->not->toBeNull();

    EstimateAnalyst::assertPrompted(fn ($prompt) => $prompt->contains('migrer notre ancien site') && $prompt->contains('Paiement en ligne'));
});

test('the AI preselects the matching items from a visitor description', function () {
    EstimateMatcher::fake([json_encode(['project_type_id' => test()->projectType->id, 'feature_ids' => [test()->feature->id, 999]])]);

    $this->postJson('/fr/estimate/suggest', ['description' => 'Un petit site pour mon restaurant avec paiement des réservations.'])
        ->assertOk()
        ->assertExactJson(['project_type_id' => $this->projectType->id, 'feature_ids' => [$this->feature->id]]);
});

test('sending an estimate requires contact details and rejects bots', function () {
    $this->post('/fr/estimate', [...estimateChoices(), 'website' => 'spam'])
        ->assertSessionHasErrors(['name', 'email', 'website']);

    expect(Engagement::query()->count())->toBe(0);
});
