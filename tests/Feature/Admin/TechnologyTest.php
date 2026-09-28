<?php

use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.technologies.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a technology', function () {
    $user = User::factory()->create();
    $category = TechnologyCategory::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => $category->id,
        'icon' => 'laravel',
    ]);

    $response->assertRedirect(route('admin.technologies.index'));
    $this->assertDatabaseHas('technologies', ['name' => 'Rust', 'icon' => 'laravel', 'category_id' => $category->id]);
});

test('a technology cannot use an icon missing from the library', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => TechnologyCategory::factory()->create()->id,
        'icon' => 'aucune-icone',
    ]);

    $response->assertSessionHasErrors(['icon' => 'Ce logo n\'existe pas dans la bibliothèque.']);
    $this->assertDatabaseMissing('technologies', ['name' => 'Rust']);
});

test('changing a technology to an icon missing from the library is refused', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create(['icon' => 'laravel']);

    $response = $this->actingAs($user)->put(route('admin.technologies.update', $technology), [
        'name' => $technology->name,
        'category_id' => $technology->category_id,
        'icon' => 'aucune-icone',
    ]);

    $response->assertSessionHasErrors('icon');
    expect($technology->fresh()->icon)->toBe('laravel');
});

test('the icon can be removed from a technology', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create(['icon' => 'laravel']);

    $this->actingAs($user)->put(route('admin.technologies.update', $technology), [
        'name' => $technology->name,
        'category_id' => $technology->category_id,
        'icon' => '',
    ])->assertRedirect(route('admin.technologies.index'));

    expect($technology->fresh()->icon)->toBeNull();
});

test('the forms offer the logos of the library and the categories', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create();
    $category = TechnologyCategory::factory()->create(['label' => ['fr' => 'Ma catégorie', 'en' => 'My category']]);

    $this->actingAs($user)->get(route('admin.technologies.create'))->assertInertia(fn ($page) => $page
        ->component('admin/technologies/create')
        ->where('icons', fn ($icons) => collect($icons)->contains(
            fn ($icon) => $icon['slug'] === 'php'
                && str_contains($icon['light_url'], '/icons/tech/php-light.svg')
                && str_contains($icon['dark_url'], '/icons/tech/php-dark.svg'),
        ))
        ->where('categories', fn ($categories) => collect($categories)->contains(
            fn ($row) => $row['id'] === $category->id && $row['label']['fr'] === 'Ma catégorie',
        )));

    $this->actingAs($user)->get(route('admin.technologies.edit', $technology))->assertInertia(fn ($page) => $page
        ->component('admin/technologies/edit')
        ->has('icons')
        ->has('categories'));
});

test('authenticated users can update a technology', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.technologies.update', $technology), [
        'name' => 'Updated name',
        'category_id' => $technology->category_id,
        'icon' => $technology->icon,
    ]);

    $response->assertRedirect(route('admin.technologies.index'));
    expect($technology->fresh()->name)->toBe('Updated name');
});

test('a technology requires an existing, non-deleted category', function () {
    $user = User::factory()->create();
    $deletedCategory = TechnologyCategory::factory()->create();
    $deletedCategory->delete();

    $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => 999999,
    ])->assertSessionHasErrors('category_id');

    $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => $deletedCategory->id,
    ])->assertSessionHasErrors('category_id');

    $this->assertDatabaseMissing('technologies', ['name' => 'Rust']);
});

test('authenticated users can delete a technology', function () {
    $user = User::factory()->create();
    $technology = Technology::factory()->create();

    $this->actingAs($user)->delete(route('admin.technologies.destroy', $technology))
        ->assertRedirect(route('admin.technologies.index'));

    $this->assertSoftDeleted($technology);
});

test('a technology description is optional, bilingual and capped at 150 characters', function () {
    $user = User::factory()->create();
    $category = TechnologyCategory::factory()->create();

    $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => $category->id,
        'description' => ['fr' => str_repeat('a', 151), 'en' => 'ok'],
    ])->assertSessionHasErrors('description.fr');

    $this->actingAs($user)->post(route('admin.technologies.store'), [
        'name' => 'Rust',
        'category_id' => $category->id,
    ])->assertRedirect(route('admin.technologies.index'));

    $this->assertDatabaseHas('technologies', ['name' => 'Rust']);
    expect(Technology::query()->where('name', 'Rust')->first()->description)->toBeNull();
});

test('creating a technology requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.technologies.store'), []);

    $response->assertSessionHasErrors(['name', 'category_id']);
});
