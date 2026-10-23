<?php

use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ConfigureEstimatorTool;
use App\Mcp\Tools\GetEstimatorTool;
use App\Mcp\Tools\ListEstimateRequestsTool;
use App\Mcp\Tools\SaveEstimateAnalysisTool;
use App\Mcp\Tools\SaveEstimatorItemTool;
use App\Models\ContentRevision;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Ai\ContentRevisions;

beforeEach(function () {
    SiteSetting::factory()->estimating()->create();
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

test('get-estimator returns the grid and its settings', function () {
    EstimatorItem::factory()->create();

    $this->server->tool(GetEstimatorTool::class)
        ->assertOk()
        ->assertSee('"risk_percent":20')
        ->assertSee('"hours_max":50');
});

test('save-estimator-item creates a hidden item; a change to an existing one waits for review', function () {
    $this->server->tool(SaveEstimatorItemTool::class, [
        'kind' => 'feature',
        'name_fr' => 'Espace client',
        'name_en' => 'Client area',
        'hours_min' => 20,
        'hours_max' => 35,
    ])->assertOk();

    $item = EstimatorItem::query()->firstOrFail();
    expect($item->is_active)->toBeFalse();

    $this->server->tool(SaveEstimatorItemTool::class, ['id' => $item->id, 'hours_max' => 40])->assertHasErrors();
    $this->server->tool(SaveEstimatorItemTool::class, ['id' => $item->id, 'hours_max' => 5, 'summary' => 'Trop court.'])->assertHasErrors();
    $this->server->tool(SaveEstimatorItemTool::class, ['id' => $item->id, 'hours_max' => 40, 'summary' => 'Plus réaliste.'])
        ->assertOk()
        ->assertSee('rien n\'a changé');

    expect($item->refresh()->hours_max)->toBe(35);

    app(ContentRevisions::class)->apply(ContentRevision::query()->sole());

    expect($item->refresh()->hours_max)->toBe(40)
        ->and($item->getTranslation('name', 'en'))->toBe('Client area');
});

test('configure-estimator proposes settings and changes to existing items as one revision', function () {
    $existing = EstimatorItem::factory()->create(['hours_max' => 50]);

    $this->server->tool(ConfigureEstimatorTool::class, [
        'summary' => 'Taux USD et nouvelle boutique.',
        'settings' => ['hourly_rates' => ['USD' => 110], 'hours_per_week' => 25],
        'items' => [
            ['ref' => 'shop', 'kind' => 'project_type', 'name_fr' => 'Boutique en ligne', 'name_en' => 'Online shop', 'hours_min' => 60, 'hours_max' => 100, 'billing' => 'fixed'],
            ['kind' => 'feature', 'name_fr' => 'Paiement', 'name_en' => 'Payment', 'hours_min' => 10, 'hours_max' => 16, 'project_types' => ['shop', (string) $existing->id]],
            ['id' => $existing->id, 'hours_max' => 60],
        ],
    ])->assertOk()->assertSee('rien n\'a changé');

    expect(EstimatorItem::query()->count())->toBe(1)
        ->and($existing->refresh()->hours_max)->toBe(50)
        ->and(SiteSetting::current()->estimatorRates()['USD'] ?? null)->not->toBe(110);

    $revision = ContentRevision::query()->sole();
    expect(app(ContentRevisions::class)->present($revision, true)['fields'])->toHaveCount(2);

    app(ContentRevisions::class)->apply($revision);

    $payment = EstimatorItem::query()->where('name->fr', 'Paiement')->firstOrFail();

    expect($payment->is_active)->toBeFalse()
        ->and($payment->projectTypes->modelKeys())->toEqualCanonicalizing([$existing->id, EstimatorItem::query()->where('name->fr', 'Boutique en ligne')->value('id')])
        ->and($existing->refresh()->hours_max)->toBe(60)
        ->and(SiteSetting::current()->estimatorRates()['USD'])->toBe(110)
        ->and(SiteSetting::current()->estimatorRates()['CHF'])->toBe(100);
});

test('configure-estimator needs a summary when it touches what visitors see, and writes nothing', function () {
    $this->server->tool(ConfigureEstimatorTool::class, ['settings' => ['hours_per_week' => 25]])->assertHasErrors();

    expect(ContentRevision::query()->count())->toBe(0)
        ->and(SiteSetting::current()->estimator_hours_per_week)->not->toBe(25);
});

test('configure-estimator creates a rate grid that new items follow by ref', function () {
    $this->server->tool(ConfigureEstimatorTool::class, [
        'rates' => [['ref' => 'advanced', 'name_fr' => 'Avancé', 'name_en' => 'Advanced', 'hourly_rates' => ['CHF' => 150]]],
        'items' => [
            ['kind' => 'project_type', 'name_fr' => 'App mobile', 'name_en' => 'Mobile app', 'hours_min' => 100, 'hours_max' => 200, 'rate' => 'advanced', 'hourly_rates' => ['EUR' => 140]],
        ],
    ])->assertOk();

    $item = EstimatorItem::query()->firstOrFail();

    expect($item->rate->getTranslation('name', 'fr'))->toBe('Avancé')
        ->and($item->hourly_rates)->toBe(['EUR' => 140]);

    $this->server->tool(GetEstimatorTool::class)->assertOk()->assertSee('"rate":'.$item->estimator_rate_id);
});

test('configure-estimator writes nothing when a reference is unknown', function () {
    $this->server->tool(ConfigureEstimatorTool::class, [
        'items' => [
            ['kind' => 'project_type', 'name_fr' => 'Vitrine', 'name_en' => 'Showcase', 'hours_min' => 20, 'hours_max' => 30],
            ['kind' => 'feature', 'name_fr' => 'Blog', 'name_en' => 'Blog', 'hours_min' => 5, 'hours_max' => 8, 'project_types' => ['nope']],
        ],
    ])->assertHasErrors();

    expect(EstimatorItem::query()->count())->toBe(0);
});

test('estimate requests are listed without contact details and an analysis can be saved', function () {
    $engagement = Engagement::factory()->create([
        'email' => 'secret@example.test',
        'estimate' => ['hourly_rate' => 100, 'currency' => 'CHF', 'features' => []],
        'message' => 'Une boutique en ligne.',
    ]);
    Engagement::factory()->create(['estimate' => null]);

    $this->server->tool(ListEstimateRequestsTool::class)
        ->assertOk()
        ->assertSee('Une boutique en ligne.')
        ->assertDontSee('secret@example.test');

    $this->server->tool(SaveEstimateAnalysisTool::class, [
        'id' => $engagement->id,
        'summary' => 'Boutique avec catalogue.',
        'questions' => ['Combien de produits ?'],
        'hours_min' => 60,
        'hours_max' => 90,
        'billing' => 'hourly',
    ])->assertOk();

    expect($engagement->refresh()->ai_analysis['questions'])->toBe(['Combien de produits ?'])
        ->and($engagement->ai_analysis['price_max'])->toBe(9000);
});
