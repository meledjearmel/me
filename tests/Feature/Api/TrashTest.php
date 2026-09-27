<?php

use App\Models\Contact;
use App\Models\Testimonial;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('la corbeille fusionne les éléments supprimés de plusieurs modèles', function () {
    $contact = Contact::factory()->create();
    $contact->delete();

    $testimonial = Testimonial::factory()->create();
    $testimonial->delete();

    Contact::factory()->create();

    $this->getJson(route('api.v1.trash.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'type', 'label', 'title', 'deleted_at']], 'links', 'meta']);
});

test('la corbeille se filtre par type', function () {
    $contact = Contact::factory()->create();
    $contact->delete();

    $testimonial = Testimonial::factory()->create();
    $testimonial->delete();

    $this->getJson(route('api.v1.trash.index', ['type' => 'contacts']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'contacts');
});

test('la corbeille se recherche par titre', function () {
    $contact = Contact::factory()->create(['name' => 'Zenitram Pixel']);
    $contact->delete();

    Contact::factory()->create(['name' => 'Someone Else'])->delete();

    $this->getJson(route('api.v1.trash.index', ['search' => 'zenitram']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('un élément supprimé peut être restauré', function () {
    $contact = Contact::factory()->create();
    $contact->delete();

    $this->patchJson(route('api.v1.trash.restore', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertNoContent();

    $this->assertNotSoftDeleted($contact);
});

test('un élément supprimé peut être effacé définitivement', function () {
    $contact = Contact::factory()->create();
    $contact->delete();

    $this->deleteJson(route('api.v1.trash.destroy', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertNoContent();

    $this->assertModelMissing($contact);
});

test('restaurer un type inconnu renvoie une 404', function () {
    $this->patchJson(route('api.v1.trash.restore', ['type' => 'not-a-type', 'id' => 1]))
        ->assertNotFound();
});

test('restaurer un élément qui n\'est pas supprimé renvoie une 404', function () {
    $contact = Contact::factory()->create();

    $this->patchJson(route('api.v1.trash.restore', ['type' => 'contacts', 'id' => $contact->id]))
        ->assertNotFound();
});
