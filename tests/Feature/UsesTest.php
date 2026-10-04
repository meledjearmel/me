<?php

use App\Enums\PublicationStatus;
use App\Enums\UsesCategory;
use App\Models\Profile;
use App\Models\Technology;
use App\Models\TechnologyCategory;
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

test('the uses seeder adds the site technologies in the matching category, without duplicates', function () {
    $infra = TechnologyCategory::query()->firstOrCreate(['key' => 'infra'], ['label' => ['fr' => 'Infra', 'en' => 'Infra']]);
    Technology::factory()->create(['name' => 'Nginx', 'category_id' => $infra->id, 'description' => ['fr' => 'Serveur web', 'en' => 'Web server']]);
    Technology::factory()->create(['name' => 'MikroTik', 'category_id' => $infra->id]);
    // Déjà décrit à la main dans le seeder : pas de second élément.
    Technology::factory()->create(['name' => 'PhpStorm', 'category_id' => $infra->id]);

    (new UsesItemSeeder)->run();

    $nginx = UsesItem::query()->where('name', 'Nginx')->sole();
    expect($nginx->category)->toBe(UsesCategory::Services)
        ->and($nginx->getTranslation('description', 'en'))->toBe('Web server')
        ->and(UsesItem::query()->where('name', 'MikroTik')->sole()->category)->toBe(UsesCategory::Hardware)
        ->and(UsesItem::query()->where('name', 'PhpStorm')->sole()->category)->toBe(UsesCategory::Development);
});

test('running the uses seeder again never overwrites an edit nor restores a deleted item', function () {
    (new UsesItemSeeder)->run();
    $count = UsesItem::query()->count();

    UsesItem::query()->where('name', 'GitHub')->sole()->update(['description' => ['fr' => 'Modifié', 'en' => 'Edited']]);
    UsesItem::query()->where('name', 'Notion')->sole()->delete();
    (new UsesItemSeeder)->run();

    expect(UsesItem::query()->count())->toBe($count - 1)
        ->and(UsesItem::query()->where('name', 'GitHub')->sole()->getTranslation('description', 'fr'))->toBe('Modifié')
        ->and(UsesItem::query()->where('name', 'Notion')->exists())->toBeFalse();
});

test('draft items stay off the uses page, which does not exist without published ones', function () {
    UsesItem::factory()->create(['name' => 'Brouillon', 'status' => PublicationStatus::Draft]);

    $this->get('/fr/uses')->assertNotFound();
    $this->get('/sitemap.xml')->assertDontSee('/fr/uses');

    UsesItem::factory()->create(['name' => 'Publié']);

    $this->get('/fr/uses')->assertOk()->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->has('categories.0.items', 1)
        ->where('categories.0.items.0.name', 'Publié'));
});

test('a deleted item no longer shows on the uses page', function () {
    UsesItem::factory()->create()->delete();

    $this->get('/fr/uses')->assertNotFound();
});
