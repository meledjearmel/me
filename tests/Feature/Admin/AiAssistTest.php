<?php

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
    TextTranslator::fake([['translation' => 'Hello world']]);

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
    TextImprover::fake([['text' => 'Texte nettement amélioré.']]);

    $this->actingAs($user)->postJson(route('admin.ai.improve'), [
        'text' => 'Texte a ameliorer',
        'locale' => 'fr',
        'tone' => 'friendly',
    ])->assertOk()->assertExactJson(['text' => 'Texte nettement amélioré.']);

    TextImprover::assertPrompted('Texte a ameliorer');
});

test('improving a text without a tone is allowed', function () {
    $user = User::factory()->create();
    TextImprover::fake([['text' => 'Texte amélioré.']]);

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

    TextTranslator::fake(function () use (&$calls): array {
        if (++$calls === 1) {
            throw new RuntimeException('404 modèle retiré');
        }

        return $calls === 2 ? ['translation' => ''] : ['translation' => 'Réponse de secours'];
    });

    $this->actingAs(User::factory()->create())->postJson(route('admin.ai.translate'), [
        'text' => 'Bonjour',
        'source_locale' => 'fr',
        'target_locale' => 'en',
    ])->assertOk()->assertExactJson(['text' => 'Réponse de secours']);

    expect($calls)->toBe(3);
    Log::shouldHaveReceived('warning')->twice();
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
    TextTranslator::fake([['translation' => 'Ok']]);
    $user = User::factory()->create();

    $payload = ['text' => 'Un', 'source_locale' => 'fr', 'target_locale' => 'en'];

    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('admin.ai.translate'), $payload)->assertTooManyRequests();
});
