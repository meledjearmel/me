<?php

use App\Enums\RevisionStatus;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ListCelebrationsTool;
use App\Mcp\Tools\ProposeRevisionTool;
use App\Mcp\Tools\SaveCelebrationTool;
use App\Models\Celebration;
use App\Models\ContentRevision;
use App\Models\User;
use App\Services\Ai\ContentRevisions;

beforeEach(function () {
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

test('save-celebration creates a draft surprise that stays inactive until the developer turns it on', function () {
    $this->server->tool(SaveCelebrationTool::class, [
        'message_fr' => 'Je viens de livrer ma première app mobile !',
        'message_en' => 'I just shipped my first mobile app!',
        'button_label_fr' => 'Bravo !',
        'button_label_en' => 'Congrats!',
        'congratulated_for' => 'Première app mobile',
        'ends_at' => '2026-12-31',
    ])->assertOk()->assertSee('brouillon');

    $celebration = Celebration::query()->where('congratulated_for', 'Première app mobile')->sole();

    expect($celebration->is_active)->toBeFalse()
        ->and($celebration->getTranslation('message', 'en'))->toBe('I just shipped my first mobile app!')
        ->and($celebration->ends_at->toDateString())->toBe('2026-12-31');
});

test('save-celebration needs both languages', function () {
    $this->server->tool(SaveCelebrationTool::class, [
        'message_fr' => 'Seulement en français',
        'button_label_fr' => 'Bravo !',
        'congratulated_for' => 'Test',
    ])->assertHasErrors();

    expect(Celebration::query()->where('congratulated_for', 'Test')->exists())->toBeFalse();
});

test('a change to an existing surprise is a revision: nothing changes until it is applied', function () {
    $celebration = Celebration::factory()->create(['is_active' => true, 'chance_percent' => 33]);
    $before = $celebration->getTranslation('message', 'fr');

    $this->server->tool(ProposeRevisionTool::class, [
        'type' => 'celebration',
        'id' => $celebration->id,
        'fr' => ['message' => 'Nouveau message', 'chance_percent' => 50],
        'en' => ['message' => 'New message'],
        'summary' => 'Message plus clair.',
    ])->assertOk()->assertSee('rien n\'a changé');

    expect($celebration->refresh()->getTranslation('message', 'fr'))->toBe($before)
        ->and($celebration->chance_percent)->toBe(33);

    $revision = ContentRevision::query()->sole();
    app(ContentRevisions::class)->apply($revision);

    expect($celebration->refresh()->getTranslation('message', 'fr'))->toBe('Nouveau message')
        ->and($celebration->getTranslation('message', 'en'))->toBe('New message')
        ->and($celebration->chance_percent)->toBe(50)
        ->and($celebration->is_active)->toBeTrue()
        ->and($revision->refresh()->status)->toBe(RevisionStatus::Applied);
});

test('a surprise revision changing the French message needs the English one too', function () {
    $celebration = Celebration::factory()->create();

    $this->server->tool(ProposeRevisionTool::class, [
        'type' => 'celebration',
        'id' => $celebration->id,
        'fr' => ['message' => 'Nouveau message'],
        'summary' => 'Message plus clair.',
    ])->assertHasErrors();

    expect(ContentRevision::query()->count())->toBe(0);
});

test('list-celebrations gives each surprise with its congratulations', function () {
    $celebration = Celebration::factory()->create(['congratulations_count' => 12]);

    $this->server->tool(ListCelebrationsTool::class)
        ->assertOk()
        ->assertSee(route('admin.celebrations.edit', $celebration))
        ->assertSee('"congratulations_count":12');
});
