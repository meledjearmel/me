<?php

use App\Enums\ProjectStatus;
use App\Mcp\Prompts\DraftProjectPrompt;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\AddProjectImageTool;
use App\Mcp\Tools\CreateTechnologyTool;
use App\Mcp\Tools\GetCatalogTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\SaveProjectDraftTool;
use App\Mcp\Tools\SaveProjectTranslationTool;
use App\Mcp\Tools\UpdateProjectDraftTool;
use App\Models\Project;
use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Models\User;
use App\Services\GitHubActivity;
use App\Services\TechnologyIconLibrary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function mcpDraftArguments(array $overrides = []): array
{
    return [
        'title' => 'Gestion de stock',
        'slug' => 'gestion-de-stock',
        'context' => 'Une PME suivait son stock sur tableur.',
        'realization' => "J'ai conçu une application Laravel.",
        'result' => "L'équipe suit son stock en temps réel.",
        'decisions' => [['choice' => 'Laravel', 'reason' => 'Écosystème complet.']],
        'key_figures' => [['value' => '3', 'label' => 'entrepôts']],
        'started_on' => '2026-02',
        'github_repository' => 'armel/stock-client',
        ...$overrides,
    ];
}

test('a draft remembers the ai client that wrote it', function () {
    $user = User::factory()->create();
    $plain = $user->createToken('Claude Code', ['mcp'])->plainTextToken;

    $this->withToken($plain)->postJson('/mcp/portfolio', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'save-project-draft', 'arguments' => mcpDraftArguments()],
    ])->assertOk();

    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    expect($draft->drafted_by)->toBe('Claude Code');

    $this->actingAs($user)->get(route('admin.projects.index'))
        ->assertInertia(fn ($page) => $page->where('projects.data.0.drafted_by', 'Claude Code'));
});

test('tools and prompts keep the exact names used in the instructions', function () {
    $plain = User::factory()->create()->createToken('Codex', ['mcp'])->plainTextToken;
    $list = fn (string $method, string $key): array => collect($this->withToken($plain)
        ->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method])
        ->json("result.{$key}"))->pluck('name')->sort()->values()->all();

    expect($list('tools/list', 'tools'))->toBe([
        'add-post-image', 'add-project-image', 'configure-estimator', 'create-technology', 'decide-appointment', 'draft-quote', 'get-catalog', 'get-content', 'get-estimator',
        'get-inbox', 'get-post', 'get-project', 'get-signing-key-backup', 'get-stats', 'get-weekly-report', 'list-app-releases', 'list-celebrations', 'list-commercial-documents', 'list-contents', 'list-estimate-requests', 'list-job-applications', 'list-proposals', 'list-revisions',
        'mark-handled', 'moderate-comment', 'moderate-testimonial', 'prepare-app-release', 'prepare-signing-key-backup', 'propose-revision', 'publish-app-release', 'read-web-page', 'save-celebration', 'save-content-draft', 'save-content-translation',
        'save-estimate-analysis', 'save-estimator-item', 'save-job-application', 'save-post-draft', 'save-post-social-texts', 'save-post-translation', 'save-project-draft', 'save-project-translation',
        'save-proposal', 'syndicate-post', 'update-project-draft',
    ])->and($list('prompts/list', 'prompts'))->toBe([
        'add-content', 'draft-project', 'plan-post-images', 'plan-project-images', 'translate-project', 'write-post', 'write-proposal',
    ]);
});

test('the server requires a token', function () {
    $this->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
});

test('the catalog lists technologies and existing projects', function () {
    $technology = Technology::factory()->create(['name' => 'Laravel']);
    Project::factory()->create(['slug' => 'app-station']);

    PortfolioServer::actingAs(User::factory()->create())->tool(GetCatalogTool::class)
        ->assertOk()
        ->assertSee('"'.$technology->id.'":"Laravel"')
        ->assertSee('app-station');
});

test('a draft is created in french only, with its relations', function () {
    $technology = Technology::factory()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['technologies' => [$technology->id]]))
        ->assertOk()
        ->assertSee('Brouillon #');

    $project = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    expect($project->status)->toBe(ProjectStatus::Draft)
        ->and($project->getTranslations('title'))->toBe(['fr' => 'Gestion de stock'])
        ->and($project->getTranslations('tagline'))->toBe([])
        ->and($project->decisions)->toBe([['choice' => ['fr' => 'Laravel', 'en' => ''], 'reason' => ['fr' => 'Écosystème complet.', 'en' => '']]])
        ->and($project->started_on->format('Y-m-d'))->toBe('2026-02-01')
        ->and($project->technologies->modelKeys())->toBe([$technology->id]);

    $this->get(route('projects.show', ['locale' => 'fr', 'project' => $project->slug]))->assertNotFound();
});

test('updating a draft keeps its english translation', function () {
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft, 'slug' => 'gestion-de-stock', 'title' => ['fr' => 'Ancien', 'en' => 'Stock management']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['id' => $draft->id]))
        ->assertOk();

    expect($draft->fresh()->getTranslations('title'))->toBe(['fr' => 'Gestion de stock', 'en' => 'Stock management']);
});

test('a published project cannot be changed through the server', function () {
    $published = Project::factory()->create(['status' => ProjectStatus::Published]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['id' => $published->id]))
        ->assertHasErrors(['Seul un brouillon existant peut être modifié.']);

    expect($published->fresh()->slug)->not->toBe('gestion-de-stock');
});

test('a draft needs a free slug and known technologies', function () {
    Project::factory()->create(['slug' => 'gestion-de-stock']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['technologies' => [999]]))
        ->assertHasErrors();

    expect(Project::query()->count())->toBe(1);
});

test('a project is read in french', function () {
    $project = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station EN']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(GetProjectTool::class, ['id' => $project->id])
        ->assertOk()
        ->assertSee('"title":"App Station"');
});

test('a draft cites another app and is linked to it', function () {
    $app = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station']]);
    $hidden = Project::factory()->create(['status' => ProjectStatus::Archived]);

    PortfolioServer::actingAs(User::factory()->create())->tool(GetCatalogTool::class)
        ->assertSee("@[App Station](project:{$app->id})");

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments([
            'realization' => "Le back-office de @[App Station](project:{$app->id}), à côté de @[Ancienne app](project:{$hidden->id}).",
            'tagline' => "Le back-office de @[App Station](project:{$app->id})",
            'related_projects' => [$app->id],
        ]))
        ->assertOk()
        ->assertSee("Mentions retirées (élément inconnu ou non publié, remplacé par son libellé) : @[Ancienne app](project:{$hidden->id})");

    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    expect($draft->getTranslation('realization', 'fr'))->toBe("Le back-office de @[App Station](project:{$app->id}), à côté de Ancienne app.")
        ->and($draft->getTranslation('tagline', 'fr'))->toBe('Le back-office de App Station')
        ->and($draft->relatedProjects()->pluck('projects.id')->all())->toBe([$app->id])
        ->and($app->relatedProjects()->pluck('projects.id')->all())->toBe([$draft->id]);
});

test('the git remote links the synced repository with its exact name', function (string $remote) {
    Cache::put(GitHubActivity::CACHE_KEY, ['available' => [
        ['full_name' => 'Armel/Stock-Client', 'private' => false, 'url' => 'https://github.com/Armel/Stock-Client'],
    ]]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['github_repository' => $remote]))
        ->assertOk()
        ->assertSee('Dépôt public Armel/Stock-Client lié');

    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    expect($draft->github_repository)->toBe('Armel/Stock-Client')
        ->and($draft->repo_url)->toBe('https://github.com/Armel/Stock-Client');
})->with([
    'https' => 'https://github.com/armel/stock-client.git',
    'ssh' => 'git@github.com:armel/stock-client.git',
    'name' => 'armel/stock-client',
]);

test('a private repository is linked without publishing its address', function () {
    Cache::put(GitHubActivity::CACHE_KEY, ['available' => [
        ['full_name' => 'armel/client-secret', 'private' => true, 'url' => 'https://github.com/armel/client-secret'],
    ]]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments([
            'github_repository' => 'git@github.com:armel/client-secret.git',
            'repo_url' => 'https://github.com/armel/client-secret',
        ]))
        ->assertOk()
        ->assertSee('Dépôt privé armel/client-secret lié');

    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    expect($draft->github_repository)->toBe('armel/client-secret')
        ->and($draft->repo_url)->toBeNull();
});

test('a gitlab remote is refused with its github mirror as a suggestion', function (string $remote) {
    Cache::put(GitHubActivity::CACHE_KEY, ['available' => [
        ['full_name' => 'armel/Stock_Client', 'private' => true, 'url' => 'https://github.com/armel/Stock_Client'],
        ['full_name' => 'armel/autre', 'private' => false, 'url' => 'https://github.com/armel/autre'],
    ]]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['github_repository' => $remote]))
        ->assertHasErrors()
        ->assertSee("n'est pas sur GitHub (gitlab.com)")
        ->assertSee('armel/Stock_Client (privé)')
        ->assertDontSee('armel/autre');

    expect(Project::query()->count())->toBe(0);
})->with([
    'https' => 'https://gitlab.com/societe/equipe/stock-client.git',
    'ssh' => 'git@gitlab.com:societe/stock-client.git',
]);

test('a gitlab remote without mirror asks the developer', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['github_repository' => 'git@gitlab.com:societe/inconnu.git']))
        ->assertHasErrors()
        ->assertSee('demande au développeur le miroir GitHub');
});

test('an unsynced repository is kept with a warning', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['github_repository' => 'https://github.com/armel/nouveau']))
        ->assertOk()
        ->assertSee('absent de la dernière synchronisation GitHub');

    expect(Project::query()->where('slug', 'gestion-de-stock')->value('github_repository'))->toBe('armel/nouveau');
});

test('a partial update only changes the fields sent', function () {
    $technology = Technology::factory()->create();
    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectDraftTool::class, mcpDraftArguments(['technologies' => [$technology->id], 'tagline' => 'Une accroche']));
    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    $draft->setTranslation('title', 'en', 'Stock management')->save();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(UpdateProjectDraftTool::class, ['id' => $draft->id, 'client' => 'Mairie de Cocody', 'tagline' => null])
        ->assertOk()
        ->assertSee('Champs modifiés : client, tagline');

    $draft->refresh();
    expect($draft->getTranslation('client', 'fr'))->toBe('Mairie de Cocody')
        ->and($draft->getTranslations('tagline'))->toBe([])
        ->and($draft->getTranslations('title'))->toBe(['fr' => 'Gestion de stock', 'en' => 'Stock management'])
        ->and($draft->getTranslation('realization', 'fr'))->toBe("J'ai conçu une application Laravel.")
        ->and($draft->decisions)->toHaveCount(1)
        ->and($draft->technologies->modelKeys())->toBe([$technology->id]);
});

test('a partial update keeps the english of the technical choices', function () {
    $draft = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'decisions' => [['choice' => ['fr' => 'Laravel', 'en' => 'Laravel'], 'reason' => ['fr' => 'Complet.', 'en' => 'Complete.']]],
    ]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(UpdateProjectDraftTool::class, ['id' => $draft->id, 'decisions' => [['choice' => 'Laravel 13', 'reason' => 'Complet et récent.']]])
        ->assertOk();

    expect($draft->fresh()->decisions)->toBe([['choice' => ['fr' => 'Laravel 13', 'en' => 'Laravel'], 'reason' => ['fr' => 'Complet et récent.', 'en' => 'Complete.']]]);
});

test('a partial update needs a field and a draft', function () {
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft]);
    $published = Project::factory()->create(['status' => ProjectStatus::Published]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(UpdateProjectDraftTool::class, ['id' => $draft->id])
        ->assertHasErrors(['Aucun champ à modifier.']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(UpdateProjectDraftTool::class, ['id' => $published->id, 'client' => 'X'])
        ->assertHasErrors(['Seul un brouillon existant peut être modifié.']);
});

test('a missing technology is created with its iconify logo', function () {
    $directory = sys_get_temp_dir().'/mcp-icons-'.Str::random(12);
    $this->app->instance(TechnologyIconLibrary::class, new TechnologyIconLibrary($directory));
    Http::fake(['api.iconify.design/logos/livewire.svg' => Http::response('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0h1v1z" fill="#f00"/></svg>')]);
    $category = TechnologyCategory::factory()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(CreateTechnologyTool::class, [
            'name' => 'Livewire',
            'category_id' => $category->id,
            'description_fr' => 'Interfaces dynamiques en PHP.',
            'description_en' => 'Dynamic interfaces in PHP.',
            'iconify_id' => 'logos:livewire',
        ])
        ->assertOk()
        ->assertSee('Technologie « Livewire » créée');

    $technology = Technology::query()->where('name', 'Livewire')->firstOrFail();
    expect($technology->icon)->toBe('livewire')
        ->and($technology->getTranslation('description', 'en'))->toBe('Dynamic interfaces in PHP.');

    File::deleteDirectory($directory);
});

test('an existing technology is not duplicated', function () {
    $technology = Technology::factory()->create(['name' => 'Laravel']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(CreateTechnologyTool::class, [
            'name' => 'laravel',
            'category_id' => $technology->category_id,
            'description_fr' => 'Framework PHP.',
            'description_en' => 'PHP framework.',
        ])
        ->assertHasErrors(["La technologie existe déjà (identifiant {$technology->id})."]);
});

test('a validated draft is translated without touching its french', function () {
    $draft = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'title' => ['fr' => 'Gestion de stock'],
        'tagline' => null,
        'context' => ['fr' => 'Le contexte.'],
        'realization' => ['fr' => 'La réalisation.'],
        'result' => ['fr' => 'Le résultat.'],
        'decisions' => [['choice' => ['fr' => 'Laravel', 'en' => ''], 'reason' => ['fr' => 'Complet.', 'en' => '']]],
        'key_figures' => [['value' => '3', 'label' => ['fr' => 'entrepôts', 'en' => '']]],
    ]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectTranslationTool::class, [
            'id' => $draft->id,
            'title' => 'Stock management',
            'tagline' => 'Should be ignored',
            'context' => 'The context.',
            'realization' => 'The build.',
            'result' => 'The result.',
            'decisions' => [['choice' => 'Laravel', 'reason' => 'Complete.']],
            'key_figure_labels' => ['warehouses'],
        ])
        ->assertOk();

    $draft->refresh();
    expect($draft->getTranslations('title'))->toBe(['fr' => 'Gestion de stock', 'en' => 'Stock management'])
        ->and($draft->getTranslation('tagline', 'en', false))->toBe('')
        ->and($draft->status)->toBe(ProjectStatus::Draft)
        ->and($draft->decisions[0]['reason'])->toBe(['fr' => 'Complet.', 'en' => 'Complete.'])
        ->and($draft->key_figures[0]['label'])->toBe(['fr' => 'entrepôts', 'en' => 'warehouses']);
});

test('a translation must cover every technical choice', function () {
    $draft = Project::factory()->create([
        'status' => ProjectStatus::Draft,
        'decisions' => [
            ['choice' => ['fr' => 'A', 'en' => ''], 'reason' => ['fr' => 'a', 'en' => '']],
            ['choice' => ['fr' => 'B', 'en' => ''], 'reason' => ['fr' => 'b', 'en' => '']],
        ],
        'key_figures' => [],
    ]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectTranslationTool::class, [
            'id' => $draft->id,
            'title' => 'Title',
            'context' => 'Context.',
            'realization' => 'Build.',
            'result' => 'Result.',
            'decisions' => [['choice' => 'A', 'reason' => 'a']],
        ])
        ->assertHasErrors(["Traduisez les 2 choix techniques, dans l'ordre."]);
});

test('a published project cannot be translated through the server', function () {
    $published = Project::factory()->create(['status' => ProjectStatus::Published]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SaveProjectTranslationTool::class, ['id' => $published->id, 'title' => 'X', 'context' => 'X', 'realization' => 'X', 'result' => 'X'])
        ->assertHasErrors(['Seul un brouillon existant peut être traduit par cet outil.']);
});

test('the draft prompt passes the developer notes along', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->prompt(DraftProjectPrompt::class, ['notes' => 'Client : la mairie de Cocody'])
        ->assertOk()
        ->assertSee('Client : la mairie de Cocody')
        ->assertSee('save-project-draft');
});

test('a project draft receives a cover and gallery images', function () {
    Storage::fake(config('media-library.disk_name'));
    $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $draft->id, 'role' => 'cover', 'data' => $png])
        ->assertOk();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $draft->id, 'role' => 'gallery', 'data' => 'data:image/png;base64,'.$png])
        ->assertOk()
        ->assertSee('(1 au total)');

    expect($draft->fresh()->hasMedia('cover'))->toBeTrue();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(GetProjectTool::class, ['id' => $draft->id])
        ->assertSee('"has_cover":true')
        ->assertSee('"gallery_count":1');
});

test('an image of a published project needs a summary, and a non image is refused', function () {
    $published = Project::factory()->create(['status' => ProjectStatus::Published]);
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $published->id, 'role' => 'cover', 'data' => base64_encode('x')])
        ->assertHasErrors(['Projet publié : l\'image sera proposée comme une révision, décris-la dans summary (ce qu\'elle montre et pourquoi).']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $draft->id, 'role' => 'gallery', 'data' => base64_encode('<svg onload="alert(1)"/>')])
        ->assertHasErrors(['Image refusée : JPEG, PNG, WebP ou GIF de 5 Mo au plus.']);
});
