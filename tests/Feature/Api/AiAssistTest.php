<?php

use App\Ai\Agents\TechnologyDescriber;
use App\Ai\Agents\TextImprover;
use App\Ai\Agents\TextTranslator;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests are rejected', function () {
    $this->postJson(route('api.v1.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertUnauthorized();
});

test('an authenticated device translates a text', function () {
    Sanctum::actingAs(User::factory()->create());
    TextTranslator::fake(['Hello world']);

    $this->postJson(route('api.v1.ai.translate'), [
        'text' => 'Bonjour le monde',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertOk()->assertExactJson(['text' => 'Hello world']);
});

test('an authenticated device improves a text', function () {
    Sanctum::actingAs(User::factory()->create());
    TextImprover::fake(['Texte amélioré.']);

    $this->postJson(route('api.v1.ai.improve'), [
        'text' => 'Texte a ameliorer',
        'locale' => 'fr',
        'tone' => 'concise',
    ])->assertOk()->assertExactJson(['text' => 'Texte amélioré.']);
});

test('an authenticated device generates a technology description', function () {
    Sanctum::actingAs(User::factory()->create());
    TechnologyDescriber::fake(['Système de versionnage pour suivre mes changements.']);
    TextTranslator::fake(['Version control system to track my changes.']);

    $this->postJson(route('api.v1.ai.describe-technology'), ['name' => 'Git'])
        ->assertOk()
        ->assertExactJson(['description' => [
            'fr' => 'Système de versionnage pour suivre mes changements.',
            'en' => 'Version control system to track my changes.',
        ]]);
});
