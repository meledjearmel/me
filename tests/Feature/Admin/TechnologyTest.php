<?php

use App\Enums\TechnologyCategory;
use App\Models\Technology;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.technologies.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a technology', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category' => TechnologyCategory::Langages->value,
        'icon' => 'rust',
    ]);

    $response->assertRedirect(route('admin.technologies.index'));
    $this->assertDatabaseHas('technologies', ['name' => 'Rust']);
});

test('authenticated users can update a technology', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.technologies.update', $technology), [
        'name' => 'Updated name',
        'category' => $technology->category->value,
        'icon' => $technology->icon,
    ]);

    $response->assertRedirect(route('admin.technologies.index'));
    expect($technology->fresh()->name)->toBe('Updated name');
});

test('authenticated users can delete a technology', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create();

    $this->actingAs($user)->delete(route('admin.technologies.destroy', $technology))
        ->assertRedirect(route('admin.technologies.index'));

    $this->assertSoftDeleted($technology);
});

test('creating a technology requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.technologies.store'), []);

    $response->assertSessionHasErrors(['name', 'category']);
});
