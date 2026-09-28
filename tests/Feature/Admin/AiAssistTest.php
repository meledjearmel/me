<?php

use App\Ai\Agents\TechnologyDescriber;
use App\Ai\Agents\TextImprover;
use App\Ai\Agents\TextTranslator;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('guests are rejected', function () {
    $this->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertUnauthorized();
});

test('an authenticated user translates a text', function () {
    $user = User::factory()->create();
    TextTranslator::fake(['Hello world']);

    $this->actingAs($user)->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour le monde',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertOk()->assertExactJson(['text' => 'Hello world']);

    TextTranslator::assertPrompted('Bonjour le monde');
});

test('the source and target locales must differ', function () {
    $user = User::factory()->create();
    TextTranslator::fake()->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'fr',
    ])->assertUnprocessable()->assertJsonValidationErrors('target_locale');

    TextTranslator::assertNeverPrompted();
});

test('an authenticated user improves a text with a chosen tone', function () {
    $user = User::factory()->create();
    TextImprover::fake(['Texte nettement amélioré.']);

    $this->actingAs($user)->postJson(route('admin.ai.improve'), [
        'text' => 'Texte a ameliorer',
        'locale' => 'fr',
        'tone' => 'friendly',
    ])->assertOk()->assertExactJson(['text' => 'Texte nettement amélioré.']);

    TextImprover::assertPrompted('Texte a ameliorer');
});

test('improving a text without a tone is allowed', function () {
    $user = User::factory()->create();
    TextImprover::fake(['Texte amélioré.']);

    $this->actingAs($user)->postJson(route('admin.ai.improve'), [
        'text' => 'Texte a ameliorer',
        'locale' => 'fr',
    ])->assertOk();
});

test('an unknown tone is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('admin.ai.improve'), [
        'text' => 'Texte',
        'locale' => 'fr',
        'tone' => 'sarcastic',
    ])->assertUnprocessable()->assertJsonValidationErrors('tone');
});

test('the assist falls back to the next provider when one fails', function () {
    Log::spy();
    config(['ai.text_assist.providers' => ['groq', 'groq-fallback', 'gemini']]);
    $calls = 0;

    TextTranslator::fake(function () use (&$calls): string {
        if (++$calls === 1) {
            throw new RuntimeException('404 modèle retiré');
        }

        return $calls === 2 ? '' : 'Réponse de secours';
    });

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertOk()->assertExactJson(['text' => 'Réponse de secours']);

    expect($calls)->toBe(3);
    Log::shouldHaveReceived('warning')->twice();
    Log::shouldNotHaveReceived('error');
});

test('a friendly error is returned when every provider fails', function () {
    Log::spy();
    TextTranslator::fake(fn () => throw new RuntimeException('quota épuisé'));

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertStatus(503)->assertJsonMissing(['quota épuisé']);
});

test('the assist is rate limited per user', function () {
    config(['ai.text_assist.limits.per_minute' => 2]);
    TextTranslator::fake(['Ok']);
    $user = User::factory()->create();

    $payload = ['text' => 'Un', 'source_locale' => 'fr', 'target_locale' => 'en'];

    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertTooManyRequests();
});

test('a friendly JSON error is returned and the failure is logged when every provider fails', function () {
    Log::spy();
    config(['ai.text_assist.providers' => ['groq', 'groq-fallback']]);
    TextTranslator::fake(fn () => throw new RuntimeException('quota épuisé'));

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertStatus(503)->assertJsonStructure(['message']);

    Log::shouldHaveReceived('warning')->twice();
    Log::shouldHaveReceived('error')->once()->withArgs(
        fn (string $message): bool => str_contains($message, 'groq (') && str_contains($message, 'groq-fallback ('),
    );
});

test('the plain text answer is trimmed and stripped of wrapping quotes', function () {
    TextImprover::fake(['  « Texte amélioré. »
']);

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.improve'), [
        'text' => 'Texte a ameliorer',
        'locale' => 'fr',
    ])->assertOk()->assertExactJson(['text' => 'Texte amélioré.']);
});

test('a slow provider caps the timeout of the next one and the budget stops the chain', function () {
    Log::spy();
    config([
        'ai.text_assist.providers' => ['groq', 'groq-fallback', 'gemini'],
        'ai.text_assist.total_budget' => 5,
        'ai.text_assist.min_provider_timeout' => 3,
    ]);
    $calls = 0;

    TextTranslator::fake(function () use (&$calls): string {
        $calls++;
        sleep(3);

        throw new RuntimeException('timeout');
    });

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertStatus(503)->assertJsonStructure(['message']);

    expect($calls)->toBe(1);
    Log::shouldHaveReceived('error')->once()->withArgs(
        fn (string $message): bool => str_contains($message, 'gemini (ignoré : budget épuisé)'),
    );
});

test('a provider:model entry targets that model and the next model answers when one fails', function () {
    config(['ai.text_assist.providers' => ['groq:model-a', 'groq:model-b']]);
    $models = [];

    TextTranslator::fake(function ($prompt, $attachments, $provider, $model) use (&$models): string {
        $models[] = $model;

        return count($models) === 1 ? throw new RuntimeException('429 quota') : 'Hello';
    });

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertOk()->assertExactJson(['text' => 'Hello']);

    expect($models)->toBe(['model-a', 'model-b']);
});

test('an authenticated user generates a technology description in both languages', function () {
    $user = User::factory()->create();
    TechnologyDescriber::fake(['Base de données orientée documents.']);
    TextTranslator::fake(['Document-oriented database.']);

    $this->actingAs($user)->postJson(route('admin.ai.describe-technology'), [
        'name' => 'MongoDB',
        'category' => 'Données',
    ])->assertOk()->assertExactJson(['description' => [
        'fr' => 'Base de données orientée documents.',
        'en' => 'Document-oriented database.',
    ]]);

    TechnologyDescriber::assertPrompted('MongoDB (catégorie : Données)');
    TextTranslator::assertPrompted('Base de données orientée documents.');
});

test('a technology description leaking the model reasoning falls back to the next provider', function () {
    config(['ai.text_assist.providers' => ['groq', 'gemini']]);
    $user = User::factory()->create();
    TechnologyDescriber::fake([
        "The user wants a description for \"Flutter\".\nDrafting ideas:\n1. Framework mobile.",
        'Crée des applications natives multiplateformes depuis un seul code.',
    ]);
    TextTranslator::fake(['Builds cross-platform native apps from a single codebase.']);

    $this->actingAs($user)->postJson(route('admin.ai.describe-technology'), ['name' => 'Flutter'])
        ->assertOk()
        ->assertJsonPath('description.fr', 'Crée des applications natives multiplateformes depuis un seul code.');
});

test('a truncated technology description falls back to the next provider', function () {
    config(['ai.text_assist.providers' => ['groq', 'gemini']]);
    $user = User::factory()->create();
    TechnologyDescriber::fake([
        'Langage orienté objet utilisé pour mes',
        'Langage orienté objet pour mes applications Android.',
    ]);
    TextTranslator::fake(['Object-oriented language for my Android apps.']);

    $this->actingAs($user)->postJson(route('admin.ai.describe-technology'), ['name' => 'Kotlin'])
        ->assertOk()
        ->assertJsonPath('description.fr', 'Langage orienté objet pour mes applications Android.');
});

test('generating a technology description requires a name', function () {
    $user = User::factory()->create();
    TechnologyDescriber::fake()->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('admin.ai.describe-technology'), [])
        ->assertUnprocessable()->assertJsonValidationErrors('name');

    TechnologyDescriber::assertNeverPrompted();
});
