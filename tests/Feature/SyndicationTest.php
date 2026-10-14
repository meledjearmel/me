<?php

use App\Ai\Agents\SocialPostWriter;
use App\Enums\SyndicationMode;
use App\Enums\SyndicationPlatform;
use App\Jobs\SendPushNotification;
use App\Jobs\SyndicatePost;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\SavePostSocialTextsTool;
use App\Mcp\Tools\SyndicatePostTool;
use App\Models\Post;
use App\Models\PostSyndication;
use App\Models\PostTag;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\HtmlToMarkdown;
use App\Services\Syndication\PostSyndicator;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Jamais de vraie publication depuis les tests.
    Http::preventStrayRequests();
    // Sauf le rendu côté serveur d'Inertia, interne au site.
    Http::allowStrayRequests(['*__inertia_ssr*']);
    Queue::fake([SendPushNotification::class, SyndicatePost::class]);
    Profile::factory()->create();
    SiteSetting::factory()->create([
        'devto_mode' => SyndicationMode::Manual,
        'devto_api_key' => 'devto-secret',
        'devto_locale' => 'en',
        'hashnode_mode' => SyndicationMode::Manual,
        'hashnode_api_key' => 'hashnode-secret',
        'hashnode_publication_id' => 'pub-123',
    ]);
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create([
        'slug' => 'mon-article',
        'title' => ['fr' => 'Mon article', 'en' => 'My article'],
        'excerpt' => ['fr' => 'Résumé', 'en' => 'Summary'],
        'body' => ['fr' => '<p>Bonjour</p>', 'en' => '<h2>Intro</h2><p>Hello <strong>world</strong>, see <a href="/en/blog/other">this</a>.</p><pre><code class="language-php">echo 1;</code></pre>'],
    ]);
    $this->post->tags()->attach(PostTag::query()->create(['name' => ['fr' => 'Laravel 12', 'en' => 'Laravel 12'], 'slug' => 'laravel']));
});

test('the article HTML becomes Markdown with absolute links and fenced code', function () {
    $markdown = app(HtmlToMarkdown::class)->convert(
        '<h2>Titre</h2><p>Du <em>texte</em> et <code>un_code</code>.</p><ul><li><p>un</p></li><li><p>deux</p></li></ul>'
        .'<ul data-type="taskList"><li data-checked="true"><label><input type="checkbox"></label><div><p>fait</p></div></li></ul>'
        .'<img src="/storage/a.png" alt="Schéma"><pre><code class="language-js">let a = 1;</code></pre><blockquote><p>Citation</p></blockquote>',
        'https://armel.test',
    );

    expect($markdown)->toContain('## Titre')
        ->toContain('Du _texte_ et `un_code`.')
        ->toContain("- un\n- deux")
        ->toContain('- [x] fait')
        ->toContain('![Schéma](https://armel.test/storage/a.png)')
        ->toContain("```js\nlet a = 1;\n```")
        ->toContain('> Citation');
});

test('an article is published on dev.to with its canonical URL, then updated in place', function () {
    Http::fake([
        'dev.to/api/articles' => Http::response(['id' => 42, 'url' => 'https://dev.to/armel/my-article'], 201),
        'dev.to/api/articles/42' => Http::response(['id' => 42, 'url' => 'https://dev.to/armel/my-article']),
    ]);

    $syndication = app(PostSyndicator::class)->syndicate($this->post, SyndicationPlatform::DevTo);

    expect($syndication->status)->toBe(PostSyndication::PUBLISHED)
        ->and($syndication->external_url)->toBe('https://dev.to/armel/my-article')
        ->and($syndication->locale)->toBe('en');

    Http::assertSent(fn (HttpRequest $request) => $request->method() === 'POST'
        && $request->hasHeader('api-key', 'devto-secret')
        && $request['article']['canonical_url'] === route('blog.show', ['locale' => 'en', 'post' => 'mon-article'])
        && $request['article']['tags'] === ['laravel12']
        && str_contains($request['article']['body_markdown'], "```php\necho 1;\n```")
        && str_contains($request['article']['body_markdown'], '[this]('.rtrim(config('app.url'), '/').'/en/blog/other)'));

    app(PostSyndicator::class)->syndicate($this->post, SyndicationPlatform::DevTo);

    Http::assertSent(fn (HttpRequest $request) => $request->method() === 'PUT' && str_ends_with($request->url(), '/api/articles/42'));
    expect($this->post->syndications()->count())->toBe(1);
});

test('an article is published on Hashnode, and a refusal is kept and notified', function () {
    Http::fakeSequence('gql.hashnode.com')
        ->push(['data' => ['publishPost' => ['post' => ['id' => 'abc', 'url' => 'https://armel.hashnode.dev/my-article']]]])
        ->push(['errors' => [['message' => 'Tag invalide']]]);

    $first = app(PostSyndicator::class)->syndicate($this->post, SyndicationPlatform::Hashnode);
    expect($first->external_id)->toBe('abc');

    Http::assertSent(fn (HttpRequest $request) => $request['variables']['input']['publicationId'] === 'pub-123'
        && $request['variables']['input']['originalArticleURL'] === route('blog.show', ['locale' => 'en', 'post' => 'mon-article']));

    $second = app(PostSyndicator::class)->syndicate($this->post, SyndicationPlatform::Hashnode);

    expect($second->status)->toBe(PostSyndication::FAILED)
        ->and($second->error)->toBe('Hashnode : Tag invalide')
        ->and($second->external_id)->toBe('abc');
    Queue::assertPushed(SendPushNotification::class);
});

test('a draft is never republished', function () {
    Http::fake();
    $draft = Post::factory()->draft()->create();

    $syndication = app(PostSyndicator::class)->syndicate($draft, SyndicationPlatform::DevTo);

    expect($syndication->status)->toBe(PostSyndication::FAILED);
    Http::assertNothingSent();
});

test('the Hashnode publication is found with the token when left empty', function () {
    Http::fakeSequence('gql.hashnode.com')
        ->push(['data' => ['me' => ['publications' => ['edges' => [['node' => ['id' => 'pub-found', 'title' => 'Armel Dev', 'url' => 'https://armel.hashnode.dev']]]]]]])
        ->push(['errors' => [['message' => 'Invalid token']]]);
    $payload = [
        'devto_mode' => 'manual', 'devto_api_key' => '', 'devto_locale' => 'en',
        'hashnode_mode' => 'manual', 'hashnode_api_key' => '', 'hashnode_locale' => 'en', 'hashnode_publication_id' => '',
    ];

    $this->actingAs($this->user)->put(route('admin.syndication.update'), $payload)->assertRedirect();
    expect(SiteSetting::current()->hashnode_publication_id)->toBe('pub-found');

    SiteSetting::current()->update(['hashnode_publication_id' => null]);
    $this->actingAs($this->user)->put(route('admin.syndication.update'), $payload)
        ->assertInertiaFlash('toast.type', 'error');
    expect(SiteSetting::current()->hashnode_publication_id)->toBeNull();
});

test('in auto mode, only posts published after the activation are queued, once', function () {
    $this->actingAs($this->user)->put(route('admin.syndication.update'), [
        'devto_mode' => 'auto', 'devto_api_key' => '', 'devto_locale' => 'en',
        'hashnode_mode' => 'off', 'hashnode_api_key' => '', 'hashnode_locale' => 'en',
    ])->assertRedirect();

    $settings = SiteSetting::current();
    expect($settings->devto_api_key)->toBe('devto-secret')
        ->and($settings->devto_auto_since)->not->toBeNull()
        ->and($settings->toArray())->not->toHaveKey('devto_api_key');

    $fresh = Post::factory()->create(['published_at' => now()->addMinute()]);
    $this->travel(2)->minutes();

    $this->artisan('posts:syndicate')->expectsOutput('1 republication(s) lancée(s).');
    $this->artisan('posts:syndicate')->expectsOutput('0 republication(s) lancée(s).');

    Queue::assertPushed(SyndicatePost::class, fn (SyndicatePost $job) => $job->postId === $fresh->id && $job->platform === 'devto');
});

test('the AI writes the share kit, whose links open each network prefilled', function () {
    SocialPostWriter::fake([json_encode([
        'linkedin' => "J'ai écrit sur Laravel.\n\n#laravel",
        'x' => str_repeat('a', 300),
        'facebook' => 'Nouvel article !',
        'whatsapp' => 'Nouvel article 🚀',
    ])]);

    $this->actingAs($this->user)->post(route('admin.posts.social-texts.generate', [$this->post, 'fr']))->assertRedirect();

    $texts = $this->post->refresh()->social_texts['fr'];
    expect(mb_strlen($texts['x']))->toBe(255);

    $this->actingAs($this->user)->get(route('admin.posts.show', $this->post))
        ->assertInertia(fn ($page) => $page
            ->where('socialKits.fr.links.whatsapp', 'https://wa.me/?text='.rawurlencode('Nouvel article 🚀 '.route('blog.show', ['locale' => 'fr', 'post' => 'mon-article'])))
            ->has('syndicationPlatforms', 2));

    $this->actingAs($this->user)->put(route('admin.posts.social-texts.update', $this->post), ['locale' => 'fr', 'texts' => ['facebook' => 'Retouché']])->assertRedirect();
    expect($this->post->refresh()->social_texts['fr']['facebook'])->toBe('Retouché')
        ->and($this->post->social_texts['fr']['linkedin'])->toContain('Laravel');
});

test('republication works through the API and MCP', function () {
    Http::fake(['dev.to/api/articles*' => Http::response(['id' => 7, 'url' => 'https://dev.to/a/b'], 201)]);
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/syndication')->assertOk()->assertJsonPath('devto.has_key', true)->assertJsonMissingPath('devto.api_key');
    $this->postJson("/api/v1/posts/{$this->post->id}/syndications/devto")->assertOk()->assertJsonPath('status', 'published');
    $this->getJson("/api/v1/posts/{$this->post->id}/syndications")->assertOk()->assertJsonPath('syndications.0.external_url', 'https://dev.to/a/b');

    $server = PortfolioServer::actingAs($this->user);
    $server->tool(SavePostSocialTextsTool::class, ['post_id' => $this->post->id, 'locale' => 'en', 'x' => 'New post'])->assertOk()->assertSee('x.com');
    $server->tool(SyndicatePostTool::class, ['post_id' => $this->post->id, 'platform' => 'devto'])->assertOk();
});
