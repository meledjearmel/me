<?php

use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\GetEstimatorTool;
use App\Mcp\Tools\ListEstimateRequestsTool;
use App\Mcp\Tools\SaveEstimateAnalysisTool;
use App\Mcp\Tools\SaveEstimatorItemTool;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use App\Models\SiteSetting;
use App\Models\User;

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

test('save-estimator-item creates a hidden item, then updates only the sent fields', function () {
    $this->server->tool(SaveEstimatorItemTool::class, [
        'kind' => 'feature',
        'name_fr' => 'Espace client',
        'name_en' => 'Client area',
        'hours_min' => 20,
        'hours_max' => 35,
    ])->assertOk();

    $item = EstimatorItem::query()->firstOrFail();
    expect($item->is_active)->toBeFalse();

    $this->server->tool(SaveEstimatorItemTool::class, ['id' => $item->id, 'hours_max' => 40])->assertOk();
    $this->server->tool(SaveEstimatorItemTool::class, ['id' => $item->id, 'hours_max' => 5])->assertHasErrors();

    expect($item->refresh()->hours_max)->toBe(40)
        ->and($item->getTranslation('name', 'en'))->toBe('Client area');
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
