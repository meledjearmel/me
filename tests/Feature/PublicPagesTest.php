<?php

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\Testimonial;

beforeEach(function () {
    Profile::factory()->create();
});

test('the about page lists experiences and education', function () {
    Experience::factory()->create();
    Education::factory()->create();

    $this->get('/en/about')->assertOk()->assertInertia(fn ($page) => $page
        ->has('experiences', 1)
        ->has('educations', 1)
    );
    $this->get('/fr/about')->assertOk();
});

test('the skills page lists domains and skills', function () {
    $domain = Domain::factory()->create();
    Skill::factory()->create(['domain_id' => $domain->id]);

    $response = $this->get('/fr/skills');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('domains', 1)
        ->has('skills', 1)
    );
});

test('the contact page is reachable', function () {
    $this->get('/fr/contact')->assertOk();
});

test('the projects index only lists published projects', function () {
    $published = Project::factory()->create(['status' => ProjectStatus::Published]);
    Project::factory()->create(['status' => ProjectStatus::Archived]);

    $response = $this->get('/fr/projects');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('projects', 1)
        ->where('projects.0.slug', $published->slug)
    );
});

test('a published project detail page is reachable', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Published]);

    $response = $this->get("/fr/projects/{$project->slug}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('project.slug', $project->slug)
    );
});

test('an archived project detail page is not found', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Archived]);

    $this->get("/fr/projects/{$project->slug}")->assertNotFound();
});

test('an unknown project slug is not found', function () {
    $this->get('/fr/projects/does-not-exist')->assertNotFound();
});

test('a project detail page exposes symmetrically related projects', function () {
    $projectA = Project::factory()->create(['status' => ProjectStatus::Published]);
    $projectB = Project::factory()->create(['status' => ProjectStatus::Published]);

    $projectA->relatedProjects()->attach($projectB->id);
    $projectB->relatedProjects()->attach($projectA->id);

    $response = $this->get("/fr/projects/{$projectA->slug}");

    $response->assertInertia(fn ($page) => $page
        ->has('project.related_projects', 1)
        ->where('project.related_projects.0.slug', $projectB->slug)
    );
});

test('the skills page exposes details and technology logos for the popup', function () {
    $skill = Skill::factory()->create([
        'details' => ['fr' => 'Détail FR', 'en' => 'Detail EN'],
    ]);
    $skill->technologies()->attach(Technology::factory()->create(['icon' => 'laravel']));

    $this->get('/en/skills')->assertOk()->assertInertia(fn ($page) => $page
        ->where('skills.0.details', 'Detail EN')
        ->has('skills.0.technologies', 1)
    );
});

test('a skill without details exposes null details', function () {
    Skill::factory()->create(['details' => null]);

    $this->get('/fr/skills')->assertInertia(fn ($page) => $page->where('skills.0.details', null));
});

test('the about page exposes the years of experience since the first mission', function () {
    $this->travelTo('2026-09-25');
    Experience::factory()->create(['start_date' => '2020-10-01', 'end_date' => null]);
    Experience::factory()->create(['start_date' => '2022-06-01', 'end_date' => null]);

    $this->get('/fr/about')->assertInertia(fn ($page) => $page->where('yearsOfExperience', 6));
});

test('the years of experience round to the nearest year', function () {
    $this->travelTo('2026-03-25');
    Experience::factory()->create(['start_date' => '2020-10-01', 'end_date' => null]);

    // 5 ans et 5 mois -> 5
    $this->get('/fr/about')->assertInertia(fn ($page) => $page->where('yearsOfExperience', 5));
});

test('the about page shows zero years without any experience', function () {
    $this->get('/fr/about')->assertInertia(fn ($page) => $page->where('yearsOfExperience', 0));
});

test('the projects index flags open source projects', function () {
    Project::factory()->create(['slug' => 'closed', 'is_open_source' => false]);
    Project::factory()->create(['slug' => 'package', 'is_open_source' => true]);

    $this->get('/fr/projects')->assertInertia(fn ($page) => $page
        ->has('projects', 2)
        ->where('projects', fn ($projects) => collect($projects)->where('is_open_source', true)->count() === 1)
    );
});

test('the project page proposes the first related project as read next', function () {
    $first = Project::factory()->create(['slug' => 'first', 'sort_order' => 1]);
    Project::factory()->create(['slug' => 'second', 'sort_order' => 2]);
    $related = Project::factory()->create(['slug' => 'third', 'sort_order' => 3]);
    $first->relatedProjects()->attach($related);

    $this->get('/fr/projects/first')->assertInertia(fn ($page) => $page
        ->where('nextProject.slug', 'third')
    );
});

test('the project page proposes the next project in order, looping back to the first', function () {
    Project::factory()->create(['slug' => 'first', 'sort_order' => 1]);
    Project::factory()->create(['slug' => 'second', 'sort_order' => 2]);

    $this->get('/fr/projects/first')->assertInertia(fn ($page) => $page->where('nextProject.slug', 'second'));
    $this->get('/fr/projects/second')->assertInertia(fn ($page) => $page->where('nextProject.slug', 'first'));
});

test('a project alone has no read next', function () {
    Project::factory()->create(['slug' => 'alone']);

    $this->get('/fr/projects/alone')->assertInertia(fn ($page) => $page->where('nextProject', null));
});

test('the project page exposes technology logos', function () {
    $project = Project::factory()->create(['slug' => 'with-tech']);
    $project->technologies()->attach(Technology::factory()->create(['icon' => 'laravel']));

    $this->get('/fr/projects/with-tech')->assertInertia(fn ($page) => $page
        ->has('project.technologies', 1)
        ->has('project.technologies.0.icon_light_url')
    );
});

test('the reviews page lists every approved review, not only the three shown on the home page', function () {
    Testimonial::factory()->count(5)->create(['status' => TestimonialStatus::Approved]);
    Testimonial::factory()->count(2)->create(['status' => TestimonialStatus::Pending]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Rejected]);

    $this->get('/fr/testimonials')->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/testimonials')
        ->has('testimonials', 5)
    );

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->has('testimonials', 3)
        ->where('testimonialCount', 5)
    );
});

test('the home page shows only the featured reviews, and a single featured review stays alone', function () {
    $featured = Testimonial::factory()->create(['status' => TestimonialStatus::Approved, 'is_featured' => true]);
    Testimonial::factory()->count(4)->create(['status' => TestimonialStatus::Approved]);

    $this->get('/fr')->assertInertia(fn ($page) => $page
        ->has('testimonials', 1)
        ->where('testimonials.0.id', $featured->id)
    );
});

test('the home page shows at most three featured reviews', function () {
    Testimonial::factory()->count(5)->create(['status' => TestimonialStatus::Approved, 'is_featured' => true]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('testimonials', 3));
});

test('without any featured review, the home page draws three approved reviews at random', function () {
    Testimonial::factory()->count(6)->create(['status' => TestimonialStatus::Approved]);
    Testimonial::factory()->count(2)->create(['status' => TestimonialStatus::Pending]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('testimonials', 3));

    $drawn = collect(range(1, 8))->map(fn () => Testimonial::forHomepage()->pluck('id')->sort()->implode(','))->unique();

    expect($drawn->count())->toBeGreaterThan(1);
});

test('a featured review that is not approved is never shown', function () {
    Testimonial::factory()->create(['status' => TestimonialStatus::Pending, 'is_featured' => true]);
    Testimonial::factory()->count(2)->create(['status' => TestimonialStatus::Approved]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('testimonials', 2));
});
