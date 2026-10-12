<?php

use App\Enums\PublicationStatus;
use App\Mcp\Prompts\AddContentPrompt;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\GetContentTool;
use App\Mcp\Tools\ListContentsTool;
use App\Mcp\Tools\SaveContentDraftTool;
use App\Mcp\Tools\SaveContentTranslationTool;
use App\Models\Certification;
use App\Models\Domain;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\User;

test('list-contents describes the fields and lists what exists', function () {
    Experience::factory()->create(['company' => 'Orange']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ListContentsTool::class, ['type' => 'experience'])
        ->assertOk()
        ->assertSee('"company":"Entreprise ou client. (obligatoire)"')
        ->assertSee('"title":"Orange"');

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ListContentsTool::class, ['type' => 'recipe'])
        ->assertHasErrors();
});

test('an experience draft is created in french with its highlights and mentions', function () {
    $app = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentDraftTool::class, ['type' => 'experience', 'fields' => [
            'company' => 'Orange',
            'role' => 'Développeur mobile',
            'start_date' => '2025-01-01',
            'description' => "J'ai construit @[App Station](project:{$app->id}).",
            'highlights' => ['Livré en 3 mois', 'Note de 4,8 sur les stores'],
            'unknown' => 'ignoré',
        ]])
        ->assertOk()
        ->assertSee('Brouillon #');

    $experience = Experience::query()->where('company', 'Orange')->firstOrFail();
    expect($experience->status)->toBe(PublicationStatus::Draft)
        ->and($experience->drafted_by)->toBe('IA')
        ->and($experience->getTranslations('role'))->toBe(['fr' => 'Développeur mobile'])
        ->and($experience->getTranslation('description', 'fr'))->toContain("@[App Station](project:{$app->id})")
        ->and($experience->highlights()->orderBy('sort_order')->get()->map(fn ($highlight) => $highlight->getTranslation('text', 'fr'))->all())
        ->toBe(['Livré en 3 mois', 'Note de 4,8 sur les stores']);
});

test('a content draft is partly updated, then translated in order', function () {
    $experience = Experience::factory()->create(['status' => PublicationStatus::Draft, 'company' => 'Orange', 'role' => ['fr' => 'Développeur'], 'description' => null]);
    $experience->highlights()->create(['text' => ['fr' => 'Première'], 'sort_order' => 0]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentDraftTool::class, ['type' => 'experience', 'id' => $experience->id, 'fields' => ['location' => 'Abidjan']])
        ->assertOk();

    expect($experience->fresh()->location)->toBe('Abidjan')
        ->and($experience->fresh()->company)->toBe('Orange')
        ->and($experience->highlights()->count())->toBe(1);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentTranslationTool::class, ['type' => 'experience', 'id' => $experience->id, 'fields' => ['role' => 'Developer', 'highlights' => []]])
        ->assertHasErrors(["Traduisez les 1 réalisations, dans l'ordre."]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentTranslationTool::class, ['type' => 'experience', 'id' => $experience->id, 'fields' => ['role' => 'Developer', 'highlights' => ['First']]])
        ->assertOk();

    $experience->refresh();
    expect($experience->getTranslations('role'))->toBe(['fr' => 'Développeur', 'en' => 'Developer'])
        ->and($experience->highlights()->first()->getTranslations('text'))->toBe(['fr' => 'Première', 'en' => 'First']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(GetContentTool::class, ['type' => 'experience', 'id' => $experience->id])
        ->assertSee('"location":"Abidjan"')
        ->assertSee('"role":"Developer"');
});

test('a skill draft keeps its domain and technologies', function () {
    $domain = Domain::factory()->create();
    $technology = Technology::factory()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentDraftTool::class, ['type' => 'skill', 'fields' => [
            'domain_id' => $domain->id,
            'name' => 'Applications mobiles',
            'technologies' => [$technology->id],
        ]])
        ->assertOk();

    $skill = Skill::query()->firstOrFail();
    expect($skill->domain_id)->toBe($domain->id)
        ->and($skill->technologies->modelKeys())->toBe([$technology->id]);
});

test('a certification needs valid dates and kind, and a published one is never changed', function () {
    $published = Certification::factory()->create(['status' => PublicationStatus::Published]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentDraftTool::class, ['type' => 'certification', 'fields' => [
            'kind' => 'diploma', 'name' => 'AWS', 'issuer' => 'AWS', 'issued_on' => now()->addYear()->format('Y-m-d'),
        ]])
        ->assertHasErrors();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveContentDraftTool::class, ['type' => 'certification', 'id' => $published->id, 'fields' => ['issuer' => 'Piraté']])
        ->assertHasErrors(['Seul un brouillon existant peut être modifié.']);

    expect(Certification::query()->count())->toBe(1);
});

test('an english-less draft can be saved from the admin, but not published', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['status' => PublicationStatus::Draft]);
    $payload = [
        'company' => 'Orange',
        'role' => ['fr' => 'Développeur'],
        'start_date' => '2025-01-01',
        'sort_order' => 0,
    ];

    $this->actingAs($user)->put(route('admin.experiences.update', $experience), [...$payload, 'status' => 'draft'])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)->put(route('admin.experiences.update', $experience), [...$payload, 'status' => 'published'])
        ->assertSessionHasErrors('role.en');
});

test('the add content prompt guides the agent', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->prompt(AddContentPrompt::class, ['type' => 'certification', 'source' => 'https://example.com/badge'])
        ->assertOk()
        ->assertSee('list-contents')
        ->assertSee('https://example.com/badge');
});
