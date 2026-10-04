<?php

use App\Enums\UsesCategory;
use App\Models\Profile;
use App\Models\UsesItem;
use Database\Seeders\UsesItemSeeder;

beforeEach(function () {
    Profile::factory()->create();
});

test('the uses page does not exist while it is empty', function () {
    $this->get('/fr/uses')->assertNotFound();
    $this->get('/sitemap.xml')->assertDontSee('/fr/uses');
});

test('the uses page groups the items by category in the category order and the page language', function () {
    UsesItem::factory()->create(['category' => UsesCategory::Services, 'name' => 'Laravel Cloud']);
    UsesItem::factory()->create(['category' => UsesCategory::Hardware, 'name' => 'Clavier', 'sort_order' => 2]);
    UsesItem::factory()->create([
        'category' => UsesCategory::Hardware,
        'name' => 'MacBook Pro',
        'sort_order' => 1,
        'description' => ['fr' => 'Mon ordinateur', 'en' => 'My computer'],
    ]);

    $this->get('/en/uses')->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/uses')
        ->where('usesEnabled', true)
        ->has('categories', 2)
        ->where('categories.0.key', 'hardware')
        ->where('categories.0.items.0.name', 'MacBook Pro')
        ->where('categories.0.items.0.description', 'My computer')
        ->where('categories.0.items.1.name', 'Clavier')
        ->where('categories.1.key', 'services'));

    $this->get('/sitemap.xml')->assertSee('/fr/uses');
});

test('the uses seeder fills an empty page but never overwrites existing content', function () {
    (new UsesItemSeeder)->run();
    $count = UsesItem::query()->count();

    expect($count)->toBeGreaterThan(0);

    UsesItem::query()->first()->update(['name' => 'Renommé']);
    (new UsesItemSeeder)->run();

    expect(UsesItem::query()->count())->toBe($count)
        ->and(UsesItem::query()->where('name', 'Renommé')->exists())->toBeTrue();
});

test('a deleted item no longer shows on the uses page', function () {
    UsesItem::factory()->create()->delete();

    $this->get('/fr/uses')->assertNotFound();
});
