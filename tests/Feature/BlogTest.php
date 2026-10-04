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
