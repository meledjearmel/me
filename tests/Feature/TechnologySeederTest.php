<?php

use App\Models\Technology;
use App\Models\TechnologyCategory;
use Database\Seeders\TechnologySeeder;

test('the seeder sets a default description only when it creates the technology', function () {
    (new TechnologySeeder)->run();

    expect(Technology::query()->where('name', 'Laravel')->first()->getTranslation('description', 'fr'))
        ->not->toBe('');
});

test('re-running the seeder never overwrites an admin-edited description', function () {
    (new TechnologySeeder)->run();

    $laravel = Technology::query()->where('name', 'Laravel')->first();
    $laravel->update(['description' => ['fr' => 'Modifié depuis l\'admin.', 'en' => 'Edited from the admin.']]);

    (new TechnologySeeder)->run();

    expect($laravel->fresh()->getTranslation('description', 'fr'))->toBe('Modifié depuis l\'admin.');
});

test('re-running the seeder never touches a description cleared from the admin', function () {
    (new TechnologySeeder)->run();

    $laravel = Technology::query()->where('name', 'Laravel')->first();
    // Formulaire admin vidé : les deux locales sont envoyées vides, jamais null
    // (voir la note sur setAttribute() dans HasTranslations : un null littéral
    // ne remet à zéro que la locale courante, pas les autres). Une locale vide
    // n'est pas conservée par getTranslations() (comportement du package).
    $laravel->update(['description' => ['fr' => '', 'en' => '']]);

    (new TechnologySeeder)->run();

    expect($laravel->fresh()->getTranslations('description'))->toBe([]);
});

test('the description backfill migration only fills a missing description', function () {
    // Les catégories par défaut existent déjà (migration 2026_09_28_130100_seed_technology_categories).
    $frameworks = TechnologyCategory::query()->where('key', 'frameworks')->firstOrFail();
    $langages = TechnologyCategory::query()->where('key', 'langages')->firstOrFail();

    // Simule une fiche seedée avant l'ajout de la colonne : pas de description du tout.
    Technology::query()->create(['name' => 'Laravel', 'category_id' => $frameworks->id, 'icon' => 'laravel']);
    Technology::query()->create(['name' => 'PHP', 'category_id' => $langages->id, 'icon' => 'php', 'description' => ['fr' => 'Modifié depuis l\'admin.', 'en' => 'Edited from the admin.']]);

    (require database_path('migrations/2026_09_28_120100_backfill_technology_descriptions.php'))->up();

    expect(Technology::query()->where('name', 'Laravel')->first()->getTranslation('description', 'fr'))
        ->toBe('Framework PHP pour bâtir des applications web solides.')
        ->and(Technology::query()->where('name', 'PHP')->first()->getTranslation('description', 'fr'))
        ->toBe('Modifié depuis l\'admin.');
});
