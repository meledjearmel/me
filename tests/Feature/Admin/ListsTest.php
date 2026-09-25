<?php

use App\Enums\ContactStatus;
use App\Enums\ProjectStatus;
use App\Enums\TechnologyCategory;
use App\Enums\TestimonialStatus;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\Testimonial;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admin lists are paginated ten at a time by default', function () {
    Contact::factory()->count(12)->create();

    $this->get(route('admin.contacts.index'))->assertInertia(fn ($page) => $page
        ->component('admin/contacts/index')
        ->has('contacts.data', 10)
        ->where('contacts.total', 12)
        ->where('contacts.last_page', 2)
        ->where('filters.per_page', 10)
    );

    $this->get(route('admin.contacts.index', ['page' => 2]))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 2)
    );
});

test('the page size can be chosen among the allowed values only', function () {
    Contact::factory()->count(30)->create();

    $this->get(route('admin.contacts.index', ['per_page' => 25]))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 25)
        ->where('filters.per_page', 25)
    );

    $this->get(route('admin.contacts.index', ['per_page' => 9999]))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 10)
        ->where('filters.per_page', 10)
    );
});

test('contacts can be searched and filtered by status', function () {
    Contact::factory()->create(['name' => 'Aminata Koné', 'status' => ContactStatus::New]);
    Contact::factory()->create(['name' => 'Aminata Diallo', 'status' => ContactStatus::Replied]);
    Contact::factory()->create(['name' => 'Yao Kouassi', 'status' => ContactStatus::New]);

    $this->get(route('admin.contacts.index', ['search' => 'aminata']))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 2)
        ->where('filters.search', 'aminata')
    );

    $this->get(route('admin.contacts.index', ['search' => 'aminata', 'status' => 'new']))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 1)
        ->where('contacts.data.0.name', 'Aminata Koné')
        ->where('filters.status', 'new')
    );
});

test('search wildcards typed by the user are taken literally', function () {
    Contact::factory()->create(['name' => 'Aminata']);
    Contact::factory()->create(['name' => 'Yao']);

    $this->get(route('admin.contacts.index', ['search' => '%']))->assertInertia(fn ($page) => $page
        ->has('contacts.data', 0)
    );
});

test('translatable fields are searchable in both languages', function () {
    Project::factory()->create(['title' => ['fr' => 'Plateforme de paiement', 'en' => 'Payment platform']]);
    Project::factory()->create(['title' => ['fr' => 'Site vitrine', 'en' => 'Showcase site']]);

    $this->get(route('admin.projects.index', ['search' => 'payment']))->assertInertia(fn ($page) => $page
        ->has('projects.data', 1)
        ->where('projects.data.0.title.fr', 'Plateforme de paiement')
    );
});

test('projects can be filtered by status, highlight and open source', function () {
    Project::factory()->create(['status' => ProjectStatus::Published, 'is_featured' => true, 'is_open_source' => false]);
    Project::factory()->create(['status' => ProjectStatus::Archived, 'is_featured' => false, 'is_open_source' => true]);

    $this->get(route('admin.projects.index', ['status' => 'archived']))->assertInertia(fn ($page) => $page->has('projects.data', 1));
    $this->get(route('admin.projects.index', ['is_featured' => '1']))->assertInertia(fn ($page) => $page->has('projects.data', 1)->where('projects.data.0.status', 'published'));
    $this->get(route('admin.projects.index', ['is_open_source' => '1']))->assertInertia(fn ($page) => $page->has('projects.data', 1)->where('projects.data.0.status', 'archived'));
});

test('testimonials can be filtered by status and highlight', function () {
    Testimonial::factory()->create(['status' => TestimonialStatus::Pending, 'is_featured' => false]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Approved, 'is_featured' => true]);

    $this->get(route('admin.testimonials.index', ['status' => 'pending']))->assertInertia(fn ($page) => $page->has('testimonials.data', 1));
    $this->get(route('admin.testimonials.index', ['is_featured' => '1']))->assertInertia(fn ($page) => $page
        ->has('testimonials.data', 1)
        ->where('testimonials.data.0.status', 'approved')
    );
});

test('technologies can be filtered by category and skills by domain', function () {
    Technology::factory()->create(['category' => TechnologyCategory::Langages]);
    Technology::factory()->create(['category' => TechnologyCategory::Infra]);

    $this->get(route('admin.technologies.index', ['category' => 'infra']))->assertInertia(fn ($page) => $page->has('technologies.data', 1));

    $domain = Domain::factory()->create();
    Skill::factory()->create(['domain_id' => $domain->id]);
    Skill::factory()->create();

    $this->get(route('admin.skills.index', ['domain_id' => $domain->id]))->assertInertia(fn ($page) => $page
        ->has('skills.data', 1)
        ->has('domains', 2)
    );
});

test('the pagination keeps the search and filters in its links', function () {
    Contact::factory()->count(12)->create(['name' => 'Aminata']);

    $this->get(route('admin.contacts.index', ['search' => 'Aminata', 'page' => 2]))->assertInertia(fn ($page) => $page
        ->where('contacts.current_page', 2)
        ->where('contacts.prev_page_url', fn ($url) => str_contains($url, 'search=Aminata'))
    );
});
