<?php

use App\Ai\Agents\EstimateAnalyst;
use App\Ai\Agents\EstimatorItemAdvisor;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\Engagement;
use App\Models\EstimatorItem;
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

test('the estimator pages render', function () {
    $item = EstimatorItem::factory()->create();

    $this->actingAs($this->user)->get(route('admin.estimator-items.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.estimator-items.create'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.estimator-items.edit', $item))->assertOk();
});

test('a project type keeps its billing and an option never has one', function () {
    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload())
        ->assertRedirect(route('admin.estimator-items.index'));
    $this->actingAs($this->user)
        ->post(route('admin.estimator-items.store'), estimatorItemPayload(['kind' => 'feature', 'billing' => 'hourly']));

    [$projectType, $feature] = EstimatorItem::query()->orderBy('id')->get();

    expect($projectType->billing)->toBe(EstimatorBilling::Hourly)
        ->and($feature->kind)->toBe(EstimatorItemKind::Feature)
        ->and($feature->billing)->toBeNull();
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
