<?php

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Technology;
use App\Models\Testimonial;

beforeEach(function () {
    Profile::factory()->create();
});

test('the home page is reachable in french', function () {
    $this->get('/fr')->assertOk();
});

test('the home page is reachable in english', function () {
    $this->get('/en')->assertOk();
});

test('root redirects to a localized url', function () {
    $this->get('/')->assertRedirect();
});

test('the home page shares the profile and visit count', function () {
    $response = $this->get('/fr');

    $response->assertInertia(fn ($page) => $page
        ->has('profile.name')
        ->has('visitCount')
    );
});

test('the home page shares the job profiles used by the hero title', function () {
    JobProfile::factory()->count(2)->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->component('public/home')
        ->has('jobProfiles', 2)
        ->has('jobProfiles.0.label')
    );
});

test('the ui locale shared with inertia follows the url', function (string $locale) {
    $this->get("/{$locale}")->assertInertia(fn ($page) => $page->where('locale', $locale));
})->with(['fr', 'en']);

test('the home page shares the featured projects and the technologies', function () {
    Project::factory()->create(['is_featured' => true, 'accent_color' => '#e0714f']);
    Project::factory()->count(2)->create(['is_featured' => false]);
    Technology::factory()->count(3)->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->has('featuredProjects', 1)
        ->where('featuredProjects.0.accent_color', '#e0714f')
        ->has('technologies', 3)
    );
});

test('the home page falls back to the first published projects when none is featured', function () {
    Project::factory()->count(2)->create(['is_featured' => false]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('featuredProjects', 2));
});

test('archived projects never appear on the home page', function () {
    Project::factory()->create(['status' => ProjectStatus::Archived, 'is_featured' => true]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('featuredProjects', 0));
});

test('technologies expose theme icons only when an icon file exists', function () {
    Technology::factory()->create(['name' => 'PHP', 'icon' => 'php']);
    Technology::factory()->create(['name' => 'React', 'icon' => 'react']);
    Technology::factory()->create(['name' => 'Inconnue', 'icon' => 'aucune-icone']);

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->where('technologies', function ($technologies) {
            $byName = collect($technologies)->keyBy('name');

            return str_contains($byName['PHP']['icon_light_url'], '/icons/tech/php-light.svg?v=')
                && str_contains($byName['PHP']['icon_dark_url'], '/icons/tech/php-dark.svg?v=')
                && str_contains($byName['React']['icon_light_url'], '/icons/tech/react.svg?v=')
                && $byName['React']['icon_light_url'] === $byName['React']['icon_dark_url']
                && $byName['Inconnue']['icon_light_url'] === null
                && $byName['Inconnue']['icon_dark_url'] === null;
        })
    );
});

test('the home page shares at most three approved testimonials without their email', function () {
    Testimonial::factory()->count(4)->create(['status' => TestimonialStatus::Approved]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Rejected]);

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->has('testimonials', 3)
        ->has('testimonials.0', fn ($testimonial) => $testimonial
            ->hasAll(['id', 'author_name', 'author_role', 'content'])
            ->missing('author_email')
        )
    );
});

test('the home page shows at most three featured projects', function () {
    Project::factory()->count(5)->create(['is_featured' => true]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('featuredProjects', 3));
});
