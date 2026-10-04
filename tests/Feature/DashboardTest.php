<?php

use App\Enums\AppointmentStatus;
use App\Enums\TestimonialStatus;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

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

test('the dashboard ranks posts and projects by visits, both languages together', function () {
    Post::factory()->create(['slug' => 'mon-article', 'title' => ['fr' => 'Mon article', 'en' => 'My post']]);
    Project::factory()->create(['slug' => 'mon-projet', 'title' => ['fr' => 'Mon projet', 'en' => 'My project']]);

    PageVisit::factory()->create(['path' => 'fr/blog/mon-article', 'visitor_hash' => 'a', 'source' => 'linkedin']);
    PageVisit::factory()->create(['path' => 'en/blog/mon-article', 'visitor_hash' => 'b', 'source' => 'linkedin']);
    PageVisit::factory()->create(['path' => 'fr/blog/mon-article', 'visitor_hash' => 'a', 'source' => null]);
    PageVisit::factory()->create(['path' => 'fr/projects/mon-projet', 'visitor_hash' => 'c', 'source' => null]);
    // Ni le flux RSS ni une page ancienne ne comptent.
    PageVisit::factory()->create(['path' => 'fr/blog/feed']);
    PageVisit::factory()->create(['path' => 'fr/projects/mon-projet'])->forceFill(['created_at' => now()->subDays(60)])->save();

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->has('visits.top_content', 2)
        ->where('visits.top_content.0', [
            'type' => 'post',
            'title' => 'Mon article',
            'url' => '/fr/blog/mon-article',
            'visits' => 3,
            'visitors' => 2,
            'top_source' => 'linkedin',
        ])
        ->where('visits.top_content.1.title', 'Mon projet')
        ->where('visits.top_content.1.visits', 1));
});

test('the api dashboard covers thirty days by default', function () {
    Sanctum::actingAs(User::factory()->create());
    PageVisit::factory()->create();
    PageVisit::factory()->create()->forceFill(['created_at' => now()->subDays(60)])->save();

    $this->getJson(route('api.v1.dashboard'))
        ->assertOk()
        ->assertJsonPath('visits.period_days', 30)
        ->assertJsonPath('visits.period', 1)
        ->assertJsonPath('visits.since', today()->subDays(29)->toDateString())
        ->assertJsonPath('visits.granularity', 'day')
        ->assertJsonCount(30, 'visits.daily')
        ->assertJsonPath('conversions.period_days', 30)
        ->assertJsonPath('cv_downloads.period_days', 30);
});

test('the api dashboard can narrow the audience to seven days', function () {
    Sanctum::actingAs(User::factory()->create());
    PageVisit::factory()->create();
    PageVisit::factory()->create()->forceFill(['created_at' => now()->subDays(10)])->save();

    $this->getJson(route('api.v1.dashboard', ['days' => '7']))
        ->assertOk()
        ->assertJsonPath('visits.period_days', 7)
        ->assertJsonPath('visits.period', 1)
        ->assertJsonPath('visits.total', 2)
        ->assertJsonCount(7, 'visits.daily');
});

test('the api dashboard draws a year month by month', function () {
    Sanctum::actingAs(User::factory()->create());
    PageVisit::factory()->create();
    PageVisit::factory()->create()->forceFill(['created_at' => now()->subDays(200)])->save();

    $this->getJson(route('api.v1.dashboard', ['days' => '365']))
        ->assertOk()
        ->assertJsonPath('visits.granularity', 'month')
        ->assertJsonPath('visits.period', 2)
        ->assertJsonCount(12, 'visits.daily')
        ->assertJsonPath('visits.daily.11', ['date' => today()->startOfMonth()->toDateString(), 'count' => 1]);
});

test('the api dashboard can go back to the very first visit', function () {
    Sanctum::actingAs(User::factory()->create());
    PageVisit::factory()->create();
    $first = now()->subYears(2)->startOfDay()->addHours(10);
    PageVisit::factory()->create()->forceFill(['created_at' => $first])->save();

    $this->getJson(route('api.v1.dashboard', ['days' => 'all']))
        ->assertOk()
        ->assertJsonPath('visits.period_days', null)
        ->assertJsonPath('visits.period', 2)
        ->assertJsonPath('visits.since', $first->toDateString())
        ->assertJsonPath('visits.granularity', 'month')
        ->assertJsonPath('conversions.since', $first->toDateString())
        ->assertJsonPath('cv_downloads.since', null);
});

test('the api dashboard can rank projects only', function () {
    Sanctum::actingAs(User::factory()->create());
    PageVisit::factory()->create(['path' => 'fr/blog/mon-article']);
    PageVisit::factory()->create(['path' => 'fr/blog/mon-article']);
    PageVisit::factory()->create(['path' => 'fr/projects/mon-projet']);

    $this->getJson(route('api.v1.dashboard', ['type' => 'project']))
        ->assertOk()
        ->assertJsonCount(1, 'visits.top_content')
        ->assertJsonPath('visits.top_content.0.type', 'project');
});

test('the api dashboard counts pending appointment requests', function () {
    Sanctum::actingAs(User::factory()->create());
    Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);

    $this->getJson(route('api.v1.dashboard'))->assertJsonPath('todo.appointments', 1);
});

test('the api dashboard rejects an unknown period or content type', function (array $query, string $field) {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.dashboard', $query))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'days' => [['days' => '14'], 'days'],
    'type' => [['type' => 'page'], 'type'],
]);
