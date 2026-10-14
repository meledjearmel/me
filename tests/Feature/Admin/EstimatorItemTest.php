<?php

use App\Ai\Agents\EstimateAnalyst;
use App\Ai\Agents\EstimatorGridDesigner;
use App\Ai\Agents\EstimatorItemAdvisor;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use App\Models\EstimatorRate;
use App\Models\SiteSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** @return array<string, mixed> */
function estimatorItemPayload(array $overrides = []): array
{
    return [
        'kind' => 'project_type',
        'name' => ['fr' => 'Application métier', 'en' => 'Business app'],
        'description' => ['fr' => null, 'en' => null],
        'hours_min' => 80,
        'hours_max' => 140,
        'billing' => 'hourly',
        'is_active' => '1',
        'sort_order' => 0,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.estimator-items.index'))->assertRedirect(route('login'));
});

test('the estimator pages render, project types, options and rates on their own pages', function () {
    $item = EstimatorItem::factory()->create();
    EstimatorItem::factory()->feature()->count(2)->create();

    $this->actingAs($this->user)->get(route('admin.estimator-items.index'))
        ->assertInertia(fn ($page) => $page->has('estimatorItems.data', 1)->where('kind', 'project_type'));
    $this->actingAs($this->user)->get(route('admin.estimator-settings.edit'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.estimator-options.index'))
        ->assertInertia(fn ($page) => $page->has('estimatorItems.data', 2));
    $this->actingAs($this->user)->get(route('admin.estimator-items.create', ['kind' => 'feature']))
        ->assertInertia(fn ($page) => $page->where('kind', 'feature'));
    $this->actingAs($this->user)->get(route('admin.estimator-items.create'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.estimator-items.edit', $item))->assertOk();
});

test('a project type keeps its billing and an option never has one', function () {
    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload())
        ->assertRedirect(route('admin.estimator-items.index'));
    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload(['kind' => 'feature', 'billing' => 'hourly', 'pricing' => 'hours']));

    [$projectType, $feature] = EstimatorItem::query()->orderBy('id')->get();

    expect($projectType->billing)->toBe(EstimatorBilling::Hourly)
        ->and($feature->kind)->toBe(EstimatorItemKind::Feature)
        ->and($feature->billing)->toBeNull();
});

test('a percentage option reserved to a project type can be created', function () {
    $projectType = EstimatorItem::factory()->create();

    $this->actingAs($this->user)->post(route('admin.estimator-items.store'), estimatorItemPayload([
        'kind' => 'feature',
        'pricing' => 'percent',
        'percent' => 25,
        'hours_min' => null,
        'hours_max' => null,
        'project_type_ids' => [$projectType->id],
    ]))->assertRedirect(route('admin.estimator-options.index'));

    $feature = EstimatorItem::query()->where('kind', 'feature')->firstOrFail();

    expect($feature->percent)->toBe(25)
        ->and($feature->hours_max)->toBe(0)
        ->and($feature->projectTypes->modelKeys())->toBe([$projectType->id]);
});

test('the AI proposes a whole grid, which is applied at once', function () {
    EstimatorGridDesigner::fake([json_encode([
        'settings' => ['hourly_rates' => ['CHF' => 120, 'GBP' => 90], 'risk_percent' => 25],
        'items' => [
            ['ref' => 'app', 'kind' => 'project_type', 'name_fr' => 'Application mobile', 'name_en' => 'Mobile app', 'hours_min' => 120, 'hours_max' => 200, 'billing' => 'fixed'],
            ['ref' => 'stores', 'kind' => 'feature', 'name_fr' => 'Publication sur les stores', 'name_en' => 'Store publishing', 'hours_min' => 8, 'hours_max' => 12, 'project_types' => ['app', 'unknown']],
            ['ref' => 'i18n', 'kind' => 'feature', 'name_fr' => 'Multilingue', 'name_en' => 'Multilingual', 'pricing' => 'percent', 'percent' => 20],
        ],
        'reasoning' => 'Une base mobile et deux options.',
    ])]);

    $proposal = $this->actingAs($this->user)
        ->postJson(route('admin.ai.propose-estimator-grid'), ['brief' => 'Je fais surtout des applications mobiles Flutter.'])
        ->assertOk()
        ->assertJsonPath('proposal.settings.hourly_rates', ['CHF' => 120])
        ->assertJsonPath('proposal.items.1.project_types', ['app'])
        ->json('proposal');

    $this->actingAs($this->user)
        ->post(route('admin.estimator-configuration.store'), $proposal)
        ->assertRedirect();

    $stores = EstimatorItem::query()->where('name->fr', 'Publication sur les stores')->firstOrFail();

    expect(EstimatorItem::query()->where('is_active', true)->count())->toBe(3)
        ->and($stores->projectTypes->first()->getTranslation('name', 'fr'))->toBe('Application mobile')
        ->and(SiteSetting::current()->estimator_risk_percent)->toBe(25);
});

test('an estimator item needs ordered hours and a billing for a project type', function () {
    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload(['hours_max' => 10, 'billing' => null]))
        ->assertSessionHasErrors(['hours_max', 'billing']);
});

test('the hourly rates, risk margin and weekly hours can be set, an empty rate hides its currency', function () {
    $this->actingAs($this->user)->put(route('admin.estimator-settings.update'), [
        'estimator_hourly_rates' => ['CHF' => 110, 'EUR' => '', 'XOF' => 35000],
        'estimator_risk_percent' => 25,
        'estimator_hours_per_week' => 28,
    ])->assertRedirect();

    expect(SiteSetting::current()->estimatorRates())->toBe(['CHF' => 110, 'XOF' => 35000])
        ->and(SiteSetting::current()->estimator_risk_percent)->toBe(25);
});

test('the AI suggests the hours and bilingual texts of an item', function () {
    EstimatorItemAdvisor::fake([json_encode([
        'name_fr' => 'Espace membre',
        'name_en' => 'Member area',
        'description_fr' => 'Inscription, connexion et profil.',
        'description_en' => 'Sign-up, login and profile.',
        'hours_min' => 24,
        'hours_max' => 16,
        'reasoning' => 'Authentification, profil, e-mails.',
    ])]);

    $this->actingAs($this->user)
        ->postJson(route('admin.ai.suggest-estimator-item'), ['kind' => 'feature', 'name' => 'Espace membre'])
        ->assertOk()
        ->assertJsonPath('item.name.en', 'Member area')
        ->assertJsonPath('item.hours_min', 16)
        ->assertJsonPath('item.hours_max', 24);
});

test('the AI analysis of a request can be rerun from its page', function () {
    SiteSetting::factory()->estimating()->create();
    $engagement = Engagement::factory()->create(['estimate' => ['hourly_rate' => 100, 'currency' => 'CHF', 'features' => []]]);
    EstimateAnalyst::fake([json_encode(['summary' => 'Refonte.', 'hours_min' => 40, 'hours_max' => 60, 'billing' => 'fixed'])]);

    $this->actingAs($this->user)
        ->post(route('admin.ai.engagements.analyze', $engagement))
        ->assertRedirect();

    expect($engagement->refresh()->ai_analysis['price_min'])->toBe(4000);
});

test('a rate grid is managed and an item follows it with its own rates on top', function () {
    $this->actingAs($this->user)->get(route('admin.estimator-rates.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.estimator-rates.create'))->assertOk();

    $this->actingAs($this->user)->post(route('admin.estimator-rates.store'), [
        'name' => ['fr' => 'Développement avancé', 'en' => 'Advanced development'],
        'hourly_rates' => ['CHF' => 150, 'EUR' => '', 'USD' => 0, 'XOF' => ''],
    ])->assertRedirect(route('admin.estimator-rates.index'));

    $rate = EstimatorRate::query()->firstOrFail();
    expect($rate->hourly_rates)->toBe(['CHF' => 150]);

    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload([
            'estimator_rate_id' => $rate->id,
            'hourly_rates' => ['CHF' => '', 'EUR' => 130, 'USD' => '', 'XOF' => ''],
        ]))
        ->assertRedirect(route('admin.estimator-items.index'));

    $item = EstimatorItem::query()->firstOrFail();
    expect($item->hourly_rates)->toBe(['EUR' => 130])
        ->and($item->hourlyRate('CHF', ['CHF' => 100, 'USD' => 90]))->toBe(150)
        ->and($item->hourlyRate('EUR', ['CHF' => 100, 'USD' => 90]))->toBe(130)
        ->and($item->hourlyRate('USD', ['CHF' => 100, 'USD' => 90]))->toBe(90);

    $this->actingAs($this->user)->get(route('admin.estimator-items.edit', $item))->assertOk();
    $this->actingAs($this->user)->delete(route('admin.estimator-rates.destroy', $rate))->assertRedirect();

    expect($item->refresh()->estimator_rate_id)->toBeNull();
});

test('rate grids are managed through the API', function () {
    Sanctum::actingAs($this->user);

    $id = $this->postJson('/api/v1/estimator-rates', [
        'name' => ['fr' => 'Conseil', 'en' => 'Consulting'],
        'hourly_rates' => ['CHF' => 180],
    ])->assertCreated()->assertJsonPath('hourly_rates', ['CHF' => 180])->json('id');

    $this->postJson('/api/v1/estimator-items', estimatorItemPayload(['estimator_rate_id' => $id, 'hourly_rates' => ['EUR' => 160]]))
        ->assertCreated()
        ->assertJsonPath('estimator_rate_id', $id)
        ->assertJsonPath('hourly_rates', ['EUR' => 160]);

    $this->getJson('/api/v1/estimator-rates')->assertOk()->assertJsonPath('data.0.items_count', 1);
    $this->deleteJson("/api/v1/estimator-rates/{$id}")->assertNoContent();
});

test('estimator items and settings are managed through the API', function () {
    Sanctum::actingAs($this->user);

    $id = $this->postJson('/api/v1/estimator-items', estimatorItemPayload(['is_active' => true]))
        ->assertCreated()
        ->assertJsonPath('hours_max', 140)
        ->assertJsonPath('billing', 'hourly')
        ->json('id');

    $this->getJson('/api/v1/estimator-items')->assertOk()->assertJsonCount(1, 'data');
    $this->putJson('/api/v1/estimator-settings', [
        'estimator_hourly_rates' => ['CHF' => 100],
        'estimator_risk_percent' => 15,
        'estimator_hours_per_week' => 30,
    ])->assertOk()->assertJsonPath('estimator_hourly_rates', ['CHF' => 100, 'EUR' => null, 'USD' => null, 'XOF' => null]);
    $this->deleteJson("/api/v1/estimator-items/{$id}")->assertNoContent();
});
