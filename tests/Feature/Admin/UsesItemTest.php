<?php

use App\Enums\UsesCategory;
use App\Models\User;
use App\Models\UsesItem;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.uses-items.index'))->assertRedirect(route('login'));
});

test('authenticated users can create, update and delete an item', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.uses-items.store'), [
        'category' => 'hardware',
        'name' => 'MacBook Pro',
        'description' => ['fr' => 'Mon ordinateur', 'en' => 'My computer'],
        'url' => 'https://apple.com',
        'sort_order' => 1,
    ])->assertRedirect(route('admin.uses-items.index'));

    $item = UsesItem::query()->sole();
    expect($item->category)->toBe(UsesCategory::Hardware)
        ->and($item->getTranslation('description', 'en'))->toBe('My computer');

    $this->actingAs($user)->put(route('admin.uses-items.update', $item), [
        'category' => 'development',
        'name' => 'PhpStorm',
        'sort_order' => 0,
    ])->assertRedirect(route('admin.uses-items.index'));

    expect($item->refresh()->name)->toBe('PhpStorm')
        ->and($item->category)->toBe(UsesCategory::Development);

    $this->actingAs($user)->delete(route('admin.uses-items.destroy', $item))->assertRedirect();

    $this->assertSoftDeleted($item);
});

test('an item needs a known category, a name and a valid link', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.uses-items.store'), [
        'category' => 'kitchen',
        'url' => 'not-a-link',
    ])->assertSessionHasErrors(['category', 'name', 'url']);
});
