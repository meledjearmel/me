<?php

use App\Enums\TestimonialStatus;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\PageVisit;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard tells me what needs my attention', function () {
    Contact::factory()->count(2)->create();
    Engagement::factory()->create();
    Testimonial::factory()->count(3)->create(['status' => TestimonialStatus::Pending]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('todo.contacts', 2)
        ->where('todo.engagements', 1)
        ->where('todo.testimonials', 3)
    );
});

test('the dashboard summarises the audience over thirty days', function () {
    PageVisit::query()->create(['path' => 'fr/about']);
    PageVisit::query()->create(['path' => 'fr/about']);
    PageVisit::query()->create(['path' => 'en/skills']);
    PageVisit::query()->create(['path' => 'fr/about'])->forceFill(['created_at' => now()->subDays(60)])->save();

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('visits.total', 4)
        ->where('visits.period', 3)
        ->where('visits.today', 3)
        ->where('visits.french', 2)
        ->where('visits.english', 1)
        ->has('visits.daily', 30)
        ->where('visits.top_pages.0.path', '/fr/about')
        ->where('visits.top_pages.0.count', 2)
    );
});

test('the dashboard reports what is missing to complete the site and the CV', function () {
    Profile::factory()->create(['cv_last_name' => null, 'cv_first_name' => null]);
    Project::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('health', fn ($items) => collect($items)->firstWhere('key', 'cv_identity')['ok'] === false
            && collect($items)->firstWhere('key', 'project_covers')['count'] === 2
            && collect($items)->firstWhere('key', 'cv_photo')['ok'] === false)
    );
});

test('the dashboard counts the content of the site', function () {
    Project::factory()->count(3)->create(['is_open_source' => true]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('content.projects.published', 3)
        ->where('content.projects.open_source', 3)
        ->has('distribution.technologies_by_category')
    );
});

test('a public page view records an anonymous visitor, its device and where it came from', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile Safari'])
        ->get('/fr/about?utm_source=LinkedIn');

    $visit = PageVisit::query()->sole();

    expect($visit->visitor_hash)->toHaveLength(64)
        ->and($visit->visitor_hash)->not->toContain('127.0.0.1')
        ->and($visit->device)->toBe('mobile')
        ->and($visit->source)->toBe('linkedin');
});

test('the dashboard counts unique visitors by source and device, and the conversions they lead to', function () {
    PageVisit::factory()->create(['visitor_hash' => 'a', 'source' => 'linkedin', 'device' => 'mobile']);
    PageVisit::factory()->create(['visitor_hash' => 'a', 'source' => 'linkedin', 'device' => 'mobile']);
    PageVisit::factory()->create(['visitor_hash' => 'b', 'source' => null, 'device' => 'desktop']);
    PageVisit::factory()->create(['visitor_hash' => 'c', 'source' => 'linkedin', 'device' => 'desktop']);
    PageVisit::factory()->create(['visitor_hash' => 'd', 'source' => 'linkedin', 'device' => 'desktop']);
    Contact::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('visits.visitors', 4)
        ->where('visits.by_source', [['label' => 'linkedin', 'count' => 3], ['label' => 'direct', 'count' => 1]])
        ->where('visits.by_device.0', ['label' => 'desktop', 'count' => 3])
        ->where('conversions.visitors', 4)
        ->where('conversions.goals', fn ($goals) => collect($goals)->firstWhere('key', 'contacts') == ['key' => 'contacts', 'count' => 1, 'rate' => 25])
    );
});
