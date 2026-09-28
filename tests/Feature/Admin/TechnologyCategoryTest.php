<?php

use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.technology-categories.index'))->assertRedirect(route('login'));
});

test('authenticated users can create and update a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.technology-categories.store'), [
        'key' => 'mobile',
        'label' => ['fr' => 'Mobile', 'en' => 'Mobile'],
        'sort_order' => 1,
    ])->assertRedirect(route('admin.technology-categories.index'));

    $category = TechnologyCategory::query()->where('key', 'mobile')->firstOrFail();

    $this->actingAs($user)->put(route('admin.technology-categories.update', $category), [
        'key' => 'mobile',
        'label' => ['fr' => 'Mobile & natif', 'en' => 'Mobile & native'],
        'sort_order' => 2,
    ])->assertRedirect(route('admin.technology-categories.index'));

    expect($category->fresh()->getTranslation('label', 'fr'))->toBe('Mobile & natif');
});

test('the category show page lists its technologies', function () {
    $category = TechnologyCategory::factory()->create();
    $technology = Technology::factory()->create(['category_id' => $category->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.technology-categories.show', $category))
        ->assertInertia(fn ($page) => $page
            ->component('admin/technology-categories/show')
            ->has('category.technologies', 1)
            ->where('category.technologies.0.id', $technology->id)
        );
});

test('creating a category requires the mandatory fields', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.technology-categories.store'), [])
        ->assertSessionHasErrors(['key', 'label.fr', 'label.en']);
});

test('a category cannot reuse an existing key', function () {
    $category = TechnologyCategory::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('admin.technology-categories.store'), [
        'key' => $category->key,
        'label' => ['fr' => 'Autre', 'en' => 'Other'],
    ])->assertSessionHasErrors('key');
});

test('deleting a category leaves its technologies, orphaned', function () {
    $category = TechnologyCategory::factory()->create();
    $technology = Technology::factory()->create(['category_id' => $category->id]);

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.technology-categories.destroy', $category))
        ->assertRedirect(route('admin.technology-categories.index'));

    $this->assertSoftDeleted($category);
    $this->assertModelExists($technology);
    expect($technology->fresh()->category)->toBeNull();
});

test('permanently deleting a category from the trash also deletes its technologies', function () {
    $user = User::factory()->create();
    $category = TechnologyCategory::factory()->create();
    $technology = Technology::factory()->create(['category_id' => $category->id]);
    $category->delete();

    $this->actingAs($user)
        ->delete(route('admin.trash.force-delete', ['type' => 'technology-categories', 'id' => $category->id]))
        ->assertRedirect(route('admin.trash.index'));

    $this->assertModelMissing($category);
    $this->assertModelMissing($technology);
});
