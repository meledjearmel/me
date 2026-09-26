<?php

use App\Enums\ProjectStatus;
use App\Models\Profile;
use App\Models\Project;

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
