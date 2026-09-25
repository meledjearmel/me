<?php

use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\TestimonialStatus;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\Testimonial;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('le tableau de bord expose ce qui attend une action', function () {
    Contact::factory()->count(2)->create();
    Engagement::factory()->create();
    Testimonial::factory()->count(3)->create(['status' => TestimonialStatus::Pending]);

    $this->getJson(route('api.v1.dashboard'))
        ->assertOk()
        ->assertJsonPath('todo.contacts', 2)
        ->assertJsonPath('todo.engagements', 1)
        ->assertJsonPath('todo.testimonials', 3)
        ->assertJsonStructure(['todo', 'visits', 'content', 'distribution', 'health', 'recent']);
});

test('la liste des contacts est paginée et filtrable par statut', function () {
    Contact::factory()->count(2)->create();
    Contact::factory()->create(['status' => ContactStatus::Replied]);

    $this->getJson(route('api.v1.contacts.index', ['status' => 'replied']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('consulter un contact nouveau le marque comme lu', function () {
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);

    $this->getJson(route('api.v1.contacts.show', $contact))->assertOk();

    expect($contact->refresh()->status)->toBe(ContactStatus::Read);
});

test('le statut d\'un contact se met à jour et rejette une valeur inconnue', function () {
    $contact = Contact::factory()->create();

    $this->patchJson(route('api.v1.contacts.update', $contact), ['status' => 'replied'])
        ->assertOk()
        ->assertJsonPath('status', 'replied');

    $this->patchJson(route('api.v1.contacts.update', $contact), ['status' => 'nope'])
        ->assertUnprocessable();
});

test('un contact peut être supprimé', function () {
    $contact = Contact::factory()->create();

    $this->deleteJson(route('api.v1.contacts.destroy', $contact))->assertNoContent();

    expect(Contact::query()->count())->toBe(0);
});

test('une demande de collaboration se marque comme traitée', function () {
    $engagement = Engagement::factory()->create();

    $this->patchJson(route('api.v1.engagements.update', $engagement), ['status' => 'handled'])
        ->assertOk()
        ->assertJsonPath('status', 'handled');

    expect($engagement->refresh()->status)->toBe(EngagementStatus::Handled);
});

test('les demandes se filtrent par type', function () {
    Engagement::factory()->count(2)->create();

    $type = Engagement::query()->first()->type->value;

    $this->getJson(route('api.v1.engagements.index', ['type' => $type]))
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'type', 'status', 'budget']]]);
});

test('un témoignage se modère', function () {
    $testimonial = Testimonial::factory()->create();

    $this->patchJson(route('api.v1.testimonials.update', $testimonial), [
        'status' => 'approved',
        'is_featured' => true,
    ])->assertOk()->assertJsonPath('status', 'approved')->assertJsonPath('is_featured', true);
});

test('trois témoignages au maximum peuvent être à la une', function () {
    Testimonial::factory()->count(Testimonial::FEATURED_LIMIT)->create(['is_featured' => true, 'status' => TestimonialStatus::Approved]);
    $testimonial = Testimonial::factory()->create();

    $this->patchJson(route('api.v1.testimonials.update', $testimonial), [
        'status' => 'approved',
        'is_featured' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('is_featured');
});
