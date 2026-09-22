<?php

use App\Models\Domain;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.domains.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a domain', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.domains.store'), [
        'key' => 'design',
        'label' => ['fr' => 'Design', 'en' => 'Design'],
        'color' => '#F472B6',
        'icon' => 'palette',
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.domains.index'));
    $this->assertDatabaseHas('domains', ['key' => 'design']);
});

test('authenticated users can update a domain', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.domains.update', $domain), [
        'key' => $domain->key,
        'label' => ['fr' => 'Nouveau nom', 'en' => 'New name'],
        'color' => $domain->color,
        'icon' => $domain->icon,
        'sort_order' => 5,
    ]);

    $response->assertRedirect(route('admin.domains.index'));
    expect($domain->fresh()->getTranslation('label', 'fr'))->toBe('Nouveau nom');
});

test('authenticated users can delete a domain', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $this->actingAs($user)->delete(route('admin.domains.destroy', $domain))
        ->assertRedirect(route('admin.domains.index'));

    $this->assertSoftDeleted($domain);
});

test('creating a domain requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.domains.store'), []);

    $response->assertSessionHasErrors(['key', 'label.fr', 'label.en', 'color', 'icon']);
});
