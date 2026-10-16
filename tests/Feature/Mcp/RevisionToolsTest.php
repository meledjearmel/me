<?php

use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\AddPostImageTool;
use App\Mcp\Tools\AddProjectImageTool;
use App\Mcp\Tools\ListRevisionsTool;
use App\Mcp\Tools\ProposeRevisionTool;
use App\Models\ContentRevision;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

const REVISION_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

function publishedProject(): Project
{
    return Project::factory()->create([
        'status' => ProjectStatus::Published,
        'tagline' => ['fr' => 'Ancienne accroche', 'en' => 'Old tagline'],
        'decisions' => [['choice' => ['fr' => 'Laravel', 'en' => 'Laravel'], 'reason' => ['fr' => 'Complet.', 'en' => 'Complete.']]],
        'key_figures' => [],
    ]);
}

test('a revision of a published project changes nothing until applied', function () {
    $project = publishedProject();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, [
            'type' => 'project',
            'id' => $project->id,
            'fr' => ['tagline' => 'Nouvelle accroche'],
            'en' => ['tagline' => 'New tagline'],
            'summary' => 'Accroche plus concrète.',
        ])
        ->assertOk()
        ->assertSee("rien n'a changé sur le site");

    expect($project->fresh()->getTranslation('tagline', 'fr'))->toBe('Ancienne accroche');

    $revision = ContentRevision::query()->sole();
    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->changes['before']['fr'])->toBe(['tagline' => 'Ancienne accroche']);

    $this->actingAs(User::factory()->create())
        ->post(route('admin.revisions.apply', $revision))
        ->assertRedirect(route('admin.revisions.index'));

    expect($project->fresh()->getTranslations('tagline'))->toBe(['fr' => 'Nouvelle accroche', 'en' => 'New tagline'])
        ->and($project->fresh()->status)->toBe(ProjectStatus::Published)
        ->and($revision->fresh()->status)->toBe(RevisionStatus::Applied);
});

test('a revision must carry the english of each translated field it changes', function () {
    $project = publishedProject();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, ['type' => 'project', 'id' => $project->id, 'fr' => ['tagline' => 'Nouvelle'], 'summary' => 'x'])
        ->assertHasErrors(["Contenu en ligne en deux langues : fournis aussi l'anglais de tagline."]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, [
            'type' => 'project', 'id' => $project->id,
            'fr' => ['decisions' => [['choice' => 'Laravel 13', 'reason' => 'Récent.'], ['choice' => 'Pest', 'reason' => 'Lisible.']]],
            'en' => ['decisions' => [['choice' => 'Laravel 13', 'reason' => 'Recent.']]],
            'summary' => 'x',
        ])
        ->assertHasErrors(["L'anglais de decisions doit compter autant d'éléments que le français, dans le même ordre."]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, ['type' => 'project', 'id' => $project->id, 'fr' => ['color' => 'rouge'], 'summary' => 'x'])
        ->assertHasErrors(['Champs inconnus pour ce contenu : color.']);

    expect(ContentRevision::query()->count())->toBe(0);
});

test('a draft is edited directly, not through a revision', function () {
    $draft = Project::factory()->create(['status' => ProjectStatus::Draft]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, ['type' => 'project', 'id' => $draft->id, 'fr' => ['client' => 'X'], 'en' => ['client' => 'X'], 'summary' => 'x'])
        ->assertHasErrors(['Ce contenu est un brouillon : modifie-le directement avec les outils de brouillon.']);
});

test('technical choices are revised in both languages in order', function () {
    $project = publishedProject();
    $revision = ContentRevision::factory()->create([
        'content_type' => 'project',
        'content_id' => $project->id,
        'changes' => [
            'fr' => ['decisions' => [['choice' => 'Laravel 13', 'reason' => 'Récent.']]],
            'en' => ['decisions' => [['choice' => 'Laravel 13', 'reason' => 'Recent.']]],
            'before' => ['fr' => [], 'en' => []],
        ],
    ]);

    $this->actingAs(User::factory()->create())->post(route('admin.revisions.apply', $revision));

    expect($project->fresh()->decisions)->toBe([['choice' => ['fr' => 'Laravel 13', 'en' => 'Laravel 13'], 'reason' => ['fr' => 'Récent.', 'en' => 'Recent.']]]);
});

test('the now page is revised through a proposal', function () {
    SiteSetting::current()->updateNowContent(['fr' => 'Je construis mon portfolio.', 'en' => 'Building my portfolio.']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, [
            'type' => 'now_page',
            'fr' => ['now_content' => "J'apprends Rust."],
            'en' => ['now_content' => 'Learning Rust.'],
            'summary' => 'Mise à jour du mois.',
        ])
        ->assertOk();

    expect(SiteSetting::current()->now_content['fr'])->toBe('Je construis mon portfolio.');

    $this->actingAs(User::factory()->create())->post(route('admin.revisions.apply', ContentRevision::query()->sole()));

    expect(SiteSetting::current()->fresh()->now_content)->toBe(['fr' => "J'apprends Rust.", 'en' => 'Learning Rust.']);
});

test('an experience and a post are revised with their english', function () {
    $experience = Experience::factory()->create(['status' => PublicationStatus::Published, 'location' => 'Abidjan']);
    $experience->highlights()->create(['text' => ['fr' => 'Avant', 'en' => 'Before'], 'sort_order' => 0]);
    $post = Post::factory()->create(['title' => ['fr' => 'Titre', 'en' => 'Title']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, [
            'type' => 'experience', 'id' => $experience->id,
            'fr' => ['location' => 'Paris', 'highlights' => ['Après']],
            'en' => ['highlights' => ['After']],
            'summary' => 'Ville et réalisation à jour.',
        ])->assertOk();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ProposeRevisionTool::class, [
            'type' => 'post', 'id' => $post->id,
            'fr' => ['title' => 'Nouveau titre'],
            'en' => ['title' => 'New title'],
            'summary' => 'Titre plus clair.',
        ])->assertOk();

    ContentRevision::query()->get()->each(fn (ContentRevision $revision) => $this->actingAs(User::factory()->create())->post(route('admin.revisions.apply', $revision)));

    expect($experience->fresh()->location)->toBe('Paris')
        ->and($experience->highlights()->first()->getTranslations('text'))->toBe(['fr' => 'Après', 'en' => 'After'])
        ->and($post->fresh()->getTranslations('title'))->toBe(['fr' => 'Nouveau titre', 'en' => 'New title'])
        ->and($post->fresh()->status)->toBe(PublicationStatus::Published);
});

test('a revision is rejected without touching the content', function () {
    $revision = ContentRevision::factory()->create();
    $before = Project::query()->find($revision->content_id)->getTranslations('tagline');

    $this->actingAs(User::factory()->create())->post(route('admin.revisions.reject', $revision))->assertRedirect();

    expect($revision->fresh()->status)->toBe(RevisionStatus::Rejected)
        ->and(Project::query()->find($revision->content_id)->getTranslations('tagline'))->toBe($before);
});

test('the admin pages show pending revisions with their before and after', function () {
    $revision = ContentRevision::factory()->create(['summary' => 'Accroche plus courte.']);

    $this->actingAs(User::factory()->create())->get(route('admin.revisions.index'))
        ->assertInertia(fn ($page) => $page->component('admin/revisions/index')->where('revisions.0.summary', 'Accroche plus courte.'));

    $this->actingAs(User::factory()->create())->get(route('admin.revisions.show', $revision))
        ->assertInertia(fn ($page) => $page->component('admin/revisions/show')->has('revision.fields', 2));
});

test('the mobile app lists, applies and refuses to reapply a revision', function () {
    Sanctum::actingAs(User::factory()->create());
    $revision = ContentRevision::factory()->create();

    $this->getJson(route('api.v1.revisions.index'))->assertOk()->assertJsonPath('data.0.id', $revision->id);
    $this->getJson(route('api.v1.revisions.show', $revision))->assertOk()->assertJsonCount(2, 'data.fields');
    $this->postJson(route('api.v1.revisions.apply', $revision))->assertOk()->assertJsonPath('data.status', 'applied');
    $this->postJson(route('api.v1.revisions.reject', $revision))->assertConflict();
});

test('the agent sees pending revisions', function () {
    ContentRevision::factory()->create(['summary' => 'Accroche plus courte.']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ListRevisionsTool::class)
        ->assertOk()
        ->assertSee('Accroche plus courte.');
});

test('an image of a published project is proposed, shown and copied only when applied', function () {
    Storage::fake(config('media-library.disk_name'));
    $project = publishedProject();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $project->id, 'role' => 'gallery', 'data' => REVISION_PNG, 'summary' => 'Capture du tableau de bord.'])
        ->assertOk()
        ->assertSee("rien n'a changé sur le site");

    $revision = ContentRevision::query()->sole();
    expect($project->fresh()->getMedia('gallery'))->toHaveCount(0);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.revisions.show', $revision))
        ->assertInertia(fn (Assert $page) => $page
            ->where('revision.image.role', 'gallery')
            ->where('revision.image.url', fn (?string $url) => filled($url)));

    $this->actingAs(User::factory()->create())->post(route('admin.revisions.apply', $revision));

    expect($project->fresh()->getMedia('gallery'))->toHaveCount(1)
        ->and($revision->fresh()->status)->toBe(RevisionStatus::Applied);
});

test('the cover of a published post is replaced through a revision', function () {
    Storage::fake(config('media-library.disk_name'));
    $post = Post::factory()->create(['status' => PublicationStatus::Published]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $post->id, 'role' => 'cover', 'data' => REVISION_PNG])
        ->assertHasErrors();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $post->id, 'role' => 'cover', 'data' => REVISION_PNG, 'summary' => 'Couverture plus lisible.'])
        ->assertOk();

    expect($post->fresh()->hasMedia('cover'))->toBeFalse();

    $this->actingAs(User::factory()->create())->post(route('admin.revisions.apply', ContentRevision::query()->sole()));

    expect($post->fresh()->hasMedia('cover'))->toBeTrue();
});

test('an image fetched by url is recognised from its content, not its content type', function () {
    Storage::fake(config('media-library.disk_name'));
    Http::fake(['93.184.216.34/*' => Http::response(base64_decode(REVISION_PNG), 200, ['Content-Type' => 'application/octet-stream'])]);
    $project = publishedProject();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddProjectImageTool::class, ['id' => $project->id, 'role' => 'cover', 'url' => 'https://93.184.216.34/cover', 'summary' => 'Nouvelle couverture.'])
        ->assertOk();

    expect(ContentRevision::query()->sole()->getFirstMedia('image')->file_name)->toEndWith('.png');
});
