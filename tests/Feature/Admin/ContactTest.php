<?php

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.contacts.index'))->assertRedirect(route('login'));
});

test('viewing a new contact marks it as read', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);

    $this->actingAs($user)->get(route('admin.contacts.edit', $contact))->assertOk();

    expect($contact->fresh()->status)->toBe(ContactStatus::Read);
});

test('authenticated users can update a contact status', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.contacts.update', $contact), [
        'status' => ContactStatus::Replied->value,
    ]);

    $response->assertRedirect(route('admin.contacts.index'));
    expect($contact->fresh()->status)->toBe(ContactStatus::Replied);
});

test('authenticated users can delete a contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)->delete(route('admin.contacts.destroy', $contact))
        ->assertRedirect(route('admin.contacts.index'));

    $this->assertSoftDeleted($contact);
});

test('updating a contact requires a valid status', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.contacts.update', $contact), [
        'status' => 'not-a-status',
    ]);

    $response->assertSessionHasErrors(['status']);
});
