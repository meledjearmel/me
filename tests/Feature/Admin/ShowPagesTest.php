<?php

use App\Models\Contact;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\ProfessionalReference;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\Testimonial;
use App\Models\User;

dataset('show pages', [
    'domains' => [fn () => Domain::factory()->create(), 'admin.domains.show', 'admin/domains/show'],
    'technologies' => [fn () => Technology::factory()->create(), 'admin.technologies.show', 'admin/technologies/show'],
    'job profiles' => [fn () => JobProfile::factory()->create(), 'admin.job-profiles.show', 'admin/job-profiles/show'],
    'skills' => [fn () => Skill::factory()->create(), 'admin.skills.show', 'admin/skills/show'],
    'educations' => [fn () => Education::factory()->create(), 'admin.educations.show', 'admin/educations/show'],
    'experiences' => [fn () => Experience::factory()->create(), 'admin.experiences.show', 'admin/experiences/show'],
    'projects' => [fn () => Project::factory()->create(), 'admin.projects.show', 'admin/projects/show'],
    'references' => [fn () => ProfessionalReference::factory()->create(), 'admin.professional-references.show', 'admin/professional-references/show'],
    'testimonials' => [fn () => Testimonial::factory()->create(), 'admin.testimonials.show', 'admin/testimonials/show'],
    'contacts' => [fn () => Contact::factory()->create(), 'admin.contacts.show', 'admin/contacts/show'],
    'engagements' => [fn () => Engagement::factory()->create(), 'admin.engagements.show', 'admin/engagements/show'],
]);

test('every admin resource has a show page', function (Closure $make, string $route, string $component) {
    $model = $make();

    $this->actingAs(User::factory()->create())
        ->get(route($route, $model))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with('show pages');

test('show pages are closed to guests', function () {
    $this->get(route('admin.domains.show', Domain::factory()->create()))->assertRedirect(route('login'));
});

test('opening a contact marks it as read', function () {
    $contact = Contact::factory()->create(['status' => 'new']);

    $this->actingAs(User::factory()->create())->get(route('admin.contacts.show', $contact))->assertOk();

    expect($contact->fresh()->status->value)->toBe('read');
});

test('the project show page carries its relations', function () {
    $project = Project::factory()->create();
    $project->relatedProjects()->attach(Project::factory()->create());

    $this->actingAs(User::factory()->create())->get(route('admin.projects.show', $project))->assertInertia(fn ($page) => $page
        ->has('project.related_projects', 1)
        ->has('project.domains')
        ->has('project.technologies')
    );
});
