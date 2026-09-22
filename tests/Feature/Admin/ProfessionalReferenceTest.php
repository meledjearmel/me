<?php

use App\Models\ProfessionalReference;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.professional-references.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a professional reference', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.professional-references.store'), [
        'name' => 'Jane Doe',
        'is_public' => '0',
        'visible_fields' => ['name', 'role'],
    ]);

    $response->assertRedirect(route('admin.professional-references.index'));
    $this->assertDatabaseHas('professional_references', ['name' => 'Jane Doe']);
});

test('authenticated users can update a professional reference', function () {
    $user = User::factory()->create();
    $reference = ProfessionalReference::factory()->create(['is_public' => false]);

    $response = $this->actingAs($user)->put(route('admin.professional-references.update', $reference), [
        'name' => $reference->name,
        'is_public' => '1',
        'visible_fields' => ['name'],
    ]);

    $response->assertRedirect(route('admin.professional-references.index'));
    expect($reference->fresh()->is_public)->toBeTrue();
});

test('authenticated users can delete a professional reference', function () {
    $user = User::factory()->create();
    $reference = ProfessionalReference::factory()->create();

    $this->actingAs($user)->delete(route('admin.professional-references.destroy', $reference))
        ->assertRedirect(route('admin.professional-references.index'));

    $this->assertSoftDeleted($reference);
});

test('creating a professional reference requires a name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.professional-references.store'), []);

    $response->assertSessionHasErrors(['name']);
});
