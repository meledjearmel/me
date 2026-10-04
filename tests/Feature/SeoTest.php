<?php

use App\Enums\ProjectStatus;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;

beforeEach(function () {
    Profile::factory()->create();
});

test('the sitemap lists public pages in both locales with hreflang alternates', function () {
    $published = Project::factory()->create(['status' => ProjectStatus::Published, 'slug' => 'visible']);
    Project::factory()->create(['status' => ProjectStatus::Archived, 'slug' => 'hidden']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    $response->assertSee(config('app.url').'/fr/about', false);
    $response->assertSee(config('app.url').'/en/projects/visible', false);
    $response->assertSee('hreflang="x-default"', false);
    $response->assertDontSee('hidden');
});

test('public pages expose an absolute site url to the seo component', function () {
    $this->get('/fr')->assertInertia(fn ($page) => $page->where('siteUrl', rtrim(config('app.url'), '/')));
});

test('private areas are marked noindex while public pages are not', function () {
    $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/fr')->assertHeaderMissing('X-Robots-Tag');
    $this->get('/sitemap.xml')->assertHeaderMissing('X-Robots-Tag');
});

test('llms.txt summarises the profile and published projects for ai engines', function () {
    Project::factory()->create(['status' => ProjectStatus::Published, 'slug' => 'visible']);
    Project::factory()->create(['status' => ProjectStatus::Archived, 'slug' => 'hidden']);

    $response = $this->get('/llms.txt');

    $response->assertOk()->assertHeaderMissing('X-Robots-Tag');
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    $response->assertSee('# '.Profile::query()->first()->name, false);
    $response->assertSee(config('app.url').'/fr/projects/visible', false);
    $response->assertDontSee('hidden');
});

test('robots.txt points to the sitemap of the current domain', function () {
    config(['app.url' => 'https://armeldev.xyz']);

    $response = $this->get('/robots.txt');

    $response->assertOk()->assertSee('Sitemap: https://armeldev.xyz/sitemap.xml', false);
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
});

test('the published posts are in the sitemap, llms.txt and the rss feed', function () {
    $published = Post::factory()->create(['slug' => 'article-visible', 'excerpt' => ['fr' => 'Le résumé', 'en' => 'The summary']]);
    Post::factory()->draft()->create(['slug' => 'brouillon-cache']);

    $this->get('/sitemap.xml')
        ->assertSee('/fr/blog/article-visible')
        ->assertSee('/en/blog')
        ->assertDontSee('brouillon-cache');

    $this->get('/llms.txt')
        ->assertSee("/fr/blog/{$published->slug}) : Le résumé", false)
        ->assertDontSee('brouillon-cache');

    $this->get('/en/blog/feed')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8')
        ->assertSee('<link>'.config('app.url').'/en/blog/article-visible</link>', false)
        ->assertSee('The summary')
        ->assertDontSee('brouillon-cache');
});

test('a disabled blog is left out of the sitemap, llms.txt and the rss feed', function () {
    SiteSetting::current()->update(['blog_enabled' => false]);
    Post::factory()->create(['slug' => 'article-visible']);

    $this->get('/sitemap.xml')->assertDontSee('/blog');
    $this->get('/llms.txt')->assertDontSee('/blog');
    $this->get('/fr/blog/feed')->assertNotFound();
});
