<?php

use App\Models\PushToken;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('un appareil s\'enregistre pour recevoir des notifications', function () {
    $this->postJson(route('api.v1.push-tokens.store'), [
        'token' => 'device-token',
        'platform' => 'ios',
    ])->assertNoContent();

    expect(PushToken::query()->where('token', 'device-token')->first())
        ->platform->toBe('ios');
});

test('renvoyer le même jeton met à jour son propriétaire', function () {
    $token = PushToken::factory()->create(['token' => 'device-token', 'platform' => 'android']);
    $newOwner = User::factory()->create();
    Sanctum::actingAs($newOwner);

    $this->postJson(route('api.v1.push-tokens.store'), [
        'token' => 'device-token',
        'platform' => 'ios',
    ])->assertNoContent();

    expect(PushToken::query()->count())->toBe(1);
    $token->refresh();
    expect($token->user_id)->toBe($newOwner->id)->and($token->platform)->toBe('ios');
});

test('la plateforme doit être ios ou android', function () {
    $this->postJson(route('api.v1.push-tokens.store'), [
        'token' => 'device-token',
        'platform' => 'windows',
    ])->assertUnprocessable()->assertJsonValidationErrors('platform');
});

test('un appareil se retire', function () {
    PushToken::factory()->create(['token' => 'device-token']);

    $this->deleteJson(route('api.v1.push-tokens.destroy'), ['token' => 'device-token'])
        ->assertNoContent();

    expect(PushToken::query()->count())->toBe(0);
});
