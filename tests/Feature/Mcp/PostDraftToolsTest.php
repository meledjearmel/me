<?php

use App\Enums\PublicationStatus;
use App\Jobs\TranslatePostTag;
use App\Mcp\Prompts\PlanPostImagesPrompt;
use App\Mcp\Prompts\WritePostPrompt;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\AddPostImageTool;
use App\Mcp\Tools\GetCatalogTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\ReadWebPageTool;
use App\Mcp\Tools\SavePostDraftTool;
use App\Mcp\Tools\SavePostTranslationTool;
use App\Models\Post;
use App\Models\PostSeries;
use App\Models\PostTag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('a post draft is created in french with mentions, tags and series', function () {
    Queue::fake();
    $app = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station']]);
    PostTag::factory()->create(['slug' => 'laravel', 'name' => ['fr' => 'Laravel', 'en' => 'Laravel']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, [
            'title' => 'Des files fiables',
            'slug' => 'des-files-fiables',
            'excerpt' => 'Comment @[App Station](project:'.$app->id.') traite ses tâches.',
            'body' => '<h2>Le problème</h2><p>Dans @[App Station](project:'.$app->id.'), les tâches échouaient.</p><script>alert(1)</script>',
            'tags' => ['Laravel', 'Files d’attente'],
            'series' => 'Coulisses',
            'series_position' => 2,
        ])
        ->assertOk()
        ->assertSee('Brouillon d\'article #')
        ->assertSee('Aperçu');

    $post = Post::query()->where('slug', 'des-files-fiables')->firstOrFail();
    expect($post->status)->toBe(PublicationStatus::Draft)
        ->and($post->getTranslation('body', 'fr'))->toContain('data-type="mention"')
        ->and($post->getTranslation('body', 'fr'))->toContain('data-id="'.$app->id.'"')
        ->and($post->getTranslation('body', 'fr'))->not->toContain('<script')
        ->and($post->getTranslation('excerpt', 'fr'))->toBe('Comment App Station traite ses tâches.')
        ->and($post->tags->pluck('slug')->sort()->values()->all())->toBe(['files-dattente', 'laravel'])
        ->and($post->series->getTranslation('name', 'fr'))->toBe('Coulisses')
        ->and($post->series_position)->toBe(2);

    Queue::assertPushed(TranslatePostTag::class, 1);
});

test('a post draft is partly updated and a published post is never changed', function () {
    $draft = Post::factory()->draft()->create(['slug' => 'brouillon', 'title' => ['fr' => 'Ancien titre', 'en' => 'Old title']]);
    $published = Post::factory()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, ['id' => $draft->id, 'title' => 'Nouveau titre'])
        ->assertOk();

    expect($draft->fresh()->getTranslations('title'))->toBe(['fr' => 'Nouveau titre', 'en' => 'Old title'])
        ->and($draft->fresh()->slug)->toBe('brouillon');

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, ['id' => $published->id, 'title' => 'Piraté'])
        ->assertHasErrors(['Seul un brouillon d\'article existant peut être modifié.']);
});

test('a post draft needs a title, a free slug and a body', function () {
    Post::factory()->create(['slug' => 'pris']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, ['title' => 'Titre', 'slug' => 'pris'])
        ->assertHasErrors();

    expect(Post::query()->count())->toBe(1);
});

test('a validated post draft is translated without touching its french', function () {
    $draft = Post::factory()->draft()->create(['title' => ['fr' => 'Titre'], 'body' => ['fr' => '<p>Bonjour</p>']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostTranslationTool::class, ['id' => $draft->id, 'title' => 'Title', 'body' => '<p>Hello</p>'])
        ->assertOk();

    $draft->refresh();
    expect($draft->getTranslations('title'))->toBe(['fr' => 'Titre', 'en' => 'Title'])
        ->and($draft->getTranslation('body', 'en'))->toBe('<p>Hello</p>')
        ->and($draft->status)->toBe(PublicationStatus::Draft);
});

test('a post is read in french', function () {
    $post = Post::factory()->create(['title' => ['fr' => 'Mon article', 'en' => 'My post']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(GetPostTool::class, ['id' => $post->id])
        ->assertOk()
        ->assertSee('"title":"Mon article"');
});

test('the write post prompt carries the subject', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->prompt(WritePostPrompt::class, ['subject' => 'Les files Laravel en production'])
        ->assertOk()
        ->assertSee('Les files Laravel en production')
        ->assertSee('save-post-draft');
});

const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

test('the catalog lists each series in reading order with its next place', function () {
    $series = PostSeries::factory()->create(['name' => ['fr' => 'Coulisses', 'en' => 'Behind the scenes']]);
    Post::factory()->create(['post_series_id' => $series->id, 'series_position' => 2, 'title' => ['fr' => 'Partie deux', 'en' => 'Part two']]);
    Post::factory()->create(['post_series_id' => $series->id, 'series_position' => 1, 'title' => ['fr' => 'Partie une', 'en' => 'Part one']]);

    PortfolioServer::actingAs(User::factory()->create())->tool(GetCatalogTool::class)
        ->assertSee('"name":"Coulisses","posts":[{"position":1')
        ->assertSee('"next_position":3');
});

test('a series place already taken is reported', function () {
    $series = PostSeries::factory()->create(['slug' => 'coulisses', 'name' => ['fr' => 'Coulisses', 'en' => 'Coulisses']]);
    $first = Post::factory()->create(['post_series_id' => $series->id, 'series_position' => 1, 'title' => ['fr' => 'Partie une', 'en' => 'Part one']]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, ['title' => 'Suite', 'slug' => 'suite', 'body' => '<p>Suite</p>', 'series' => 'Coulisses', 'series_position' => 1])
        ->assertOk()
        ->assertSee("la partie 1 de la série est déjà « Partie une » (#{$first->id})");

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(SavePostDraftTool::class, ['title' => 'Autre', 'slug' => 'autre', 'body' => '<p>Autre</p>', 'series' => 'coulisses', 'series_position' => 2])
        ->assertSee('Placé en partie 2 de la série « Coulisses »');

    expect(PostSeries::query()->count())->toBe(1);
});

test('a web page is read for inspiration, without its noise', function () {
    Http::fake(['93.184.216.34/*' => Http::response('<html><head><title>Les files</title><meta name="description" content="Un guide"></head><body><nav>Menu</nav><article><h2>Pourquoi</h2><p>Parce que.</p><pre>dispatch($job);</pre></article><script>track()</script></body></html>')]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ReadWebPageTool::class, ['url' => 'https://93.184.216.34/guide'])
        ->assertOk()
        ->assertSee('Titre : Les files')
        ->assertSee('Description : Un guide')
        ->assertSee('## Pourquoi')
        ->assertSee('dispatch($job);')
        ->assertDontSee('Menu')
        ->assertDontSee('track()');
});

test('internal addresses are never fetched, even after a redirect', function () {
    Http::fake(['93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin'])]);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ReadWebPageTool::class, ['url' => 'http://192.168.1.10/'])
        ->assertHasErrors(['Adresse interne refusée.']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(ReadWebPageTool::class, ['url' => 'https://93.184.216.34/'])
        ->assertHasErrors(['Adresse interne refusée.']);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '127.0.0.1'));
});

test('a content image is stored and its tag returned, and a cover is set', function () {
    Storage::fake(config('media-library.disk_name'));
    Http::fake(['93.184.216.34/*' => Http::response(base64_decode(TINY_PNG))]);
    $draft = Post::factory()->draft()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $draft->id, 'role' => 'content', 'data' => 'data:image/png;base64,'.TINY_PNG, 'alt' => 'Schéma des files'])
        ->assertOk()
        ->assertSee('<img src=')
        ->assertSee('alt="Schéma des files"');

    expect(Storage::disk(config('media-library.disk_name'))->files('blog'))->toHaveCount(1);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $draft->id, 'role' => 'cover', 'url' => 'https://93.184.216.34/cover.png'])
        ->assertOk();

    expect($draft->fresh()->getFirstMediaUrl('cover'))->not->toBe('');
});

test('only images reach a draft', function () {
    Storage::fake(config('media-library.disk_name'));
    $draft = Post::factory()->draft()->create();
    $published = Post::factory()->create();

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $draft->id, 'role' => 'cover', 'data' => base64_encode('<?php echo 1;')])
        ->assertHasErrors(['Image refusée : JPEG, PNG, WebP ou GIF de 5 Mo au plus.']);

    PortfolioServer::actingAs(User::factory()->create())
        ->tool(AddPostImageTool::class, ['id' => $published->id, 'role' => 'cover', 'data' => TINY_PNG])
        ->assertHasErrors(["Seul un brouillon d'article existant peut recevoir des images."]);
});

test('the image plan prompt asks for placements and generation prompts', function () {
    PortfolioServer::actingAs(User::factory()->create())
        ->prompt(PlanPostImagesPrompt::class, ['id' => 7])
        ->assertOk()
        ->assertSee('#7')
        ->assertSee('prompt de génération professionnel')
        ->assertSee('16:9');

    PortfolioServer::actingAs(User::factory()->create())
        ->prompt(WritePostPrompt::class, ['subject' => 'Les files', 'source_url' => 'https://example.com/files'])
        ->assertSee('read-web-page')
        ->assertSee("Plan d'images");
});
