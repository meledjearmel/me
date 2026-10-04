<?php

use App\Models\Post;
use App\Models\PostTag;
use App\Models\Profile;
use App\Models\SiteSetting;

beforeEach(function () {
    Profile::factory()->create();
});

test('the blog lists only the published posts', function () {
    $published = Post::factory()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();

    $this->get('/fr/blog')->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/blog/index')
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $published->id)
        ->where('total', 1));
});

test('the blog can be filtered by tag', function () {
    $tag = PostTag::factory()->create(['slug' => 'laravel']);
    $tagged = Post::factory()->hasAttached($tag, [], 'tags')->create();
    Post::factory()->create();

    $this->get('/fr/blog?tag=laravel')->assertInertia(fn ($page) => $page
        ->where('activeTag', 'laravel')
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $tagged->id)
        ->where('tags.0.count', 1));
});

test('an article shows its body with anchored headings and a table of contents', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => '<h2>Pourquoi Laravel</h2><p>Texte</p><h3>Détails</h3><h2>Pourquoi Laravel</h2>'],
    ]);

    $this->get("/fr/blog/{$post->slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/blog/show')
        ->where('post.toc.0', ['id' => 'pourquoi-laravel', 'text' => 'Pourquoi Laravel', 'level' => 2])
        ->where('post.toc.1.level', 3)
        ->where('post.toc.2.id', 'pourquoi-laravel-2')
        ->where('post.body', fn (string $body) => str_contains($body, '<h2 id="pourquoi-laravel">Pourquoi Laravel</h2>')));
});

test('an untranslated article falls back to french on the english site', function () {
    $post = Post::factory()->create([
        'title' => ['fr' => 'Titre français'],
        'body' => ['fr' => '<p>Contenu français</p>'],
    ]);

    $this->get("/en/blog/{$post->slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->where('post.title', 'Titre français')
        ->where('post.content_locale', 'fr'));
});

test('drafts, scheduled and trashed posts are not reachable', function () {
    $draft = Post::factory()->draft()->create();
    $scheduled = Post::factory()->scheduled()->create();
    $trashed = Post::factory()->create();
    $trashed->delete();

    $this->get("/fr/blog/{$draft->slug}")->assertNotFound();
    $this->get("/fr/blog/{$scheduled->slug}")->assertNotFound();
    $this->get("/fr/blog/{$trashed->slug}")->assertNotFound();
});

test('the blog can be searched in titles, tags and content, ignoring accents', function () {
    $tag = PostTag::factory()->create(['name' => ['fr' => 'Déploiement']]);
    $byContent = Post::factory()->create(['body' => ['fr' => '<p>Configurer une <strong>file d’attente</strong> Horizon</p>']]);
    $byTag = Post::factory()->hasAttached($tag, [], 'tags')->create();
    Post::factory()->create(['title' => ['fr' => 'Sans rapport']]);
    Post::factory()->draft()->create(['body' => ['fr' => '<p>Horizon en brouillon</p>']]);

    $this->get('/fr/blog?q=HORIZON')->assertOk()->assertInertia(fn ($page) => $page
        ->where('search', 'HORIZON')
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $byContent->id));

    $this->get('/fr/blog?q=deploiement')->assertInertia(fn ($page) => $page
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $byTag->id));

    $this->get("/fr/blog?q=horizon&tag={$tag->slug}")->assertInertia(fn ($page) => $page->has('posts.data', 0));
});

test('code blocks with a known language are highlighted', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => '<pre><code class="language-php">$total = 1; // &lt;b&gt;x&lt;/b&gt;</code></pre><pre><code>brut</code></pre>'],
    ]);

    $this->get("/fr/blog/{$post->slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->where('post.body', fn (string $body) => str_contains($body, '<pre data-language="php"><code class="language-php"><span class="hl-variable">$total</span>')
            && str_contains($body, '<span class="hl-comment">// &lt;b&gt;x&lt;/b&gt;</span>')
            && str_contains($body, '<pre><code>brut</code></pre>')));
});

test('a draft or scheduled post can be previewed through its signed link only', function () {
    $draft = Post::factory()->draft()->create();
    $scheduled = Post::factory()->scheduled()->create();

    $this->get($draft->previewUrl())->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/blog/show')
        ->where('post.id', $draft->id)
        ->where('preview', true));
    $this->get($scheduled->previewUrl('en'))->assertOk();

    $this->get("/fr/blog/{$draft->slug}/preview")->assertForbidden();
    $this->get(str_replace('/fr/', '/en/', $draft->previewUrl()))->assertForbidden();
});

test('related posts favour shared tags', function () {
    $tag = PostTag::factory()->create();
    $post = Post::factory()->hasAttached($tag, [], 'tags')->create();
    $sibling = Post::factory()->hasAttached($tag, [], 'tags')->create(['published_at' => now()->subYear()]);
    Post::factory()->create(['published_at' => now()]);

    $this->get("/fr/blog/{$post->slug}")->assertInertia(fn ($page) => $page
        ->has('relatedPosts', 2)
        ->where('relatedPosts.0.id', $sibling->id));
});

test('the whole blog is hidden when it is disabled', function () {
    SiteSetting::current()->update(['blog_enabled' => false]);
    $post = Post::factory()->create();

    $this->get('/fr/blog')->assertNotFound();
    $this->get("/fr/blog/{$post->slug}")->assertNotFound();
});
