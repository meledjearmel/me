<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('un utilisateur obtient un jeton avec des identifiants valides', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    expect($user->tokens()->count())->toBe(1);
});

test('la connexion échoue avec un mauvais mot de passe', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'mauvais',
        'device_name' => 'iPhone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('la connexion est refusée pour un compte avec double authentification', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

test('me renvoie l\'utilisateur connecté et refuse les anonymes', function () {
    $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.auth.me'))->assertOk()->assertJsonPath('id', $user->id);
});

test('la déconnexion révoque le jeton', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;

    $this->withToken($token)->postJson(route('api.v1.auth.logout'))->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('les routes de gestion refusent les anonymes', function () {
    $this->getJson(route('api.v1.dashboard'))->assertUnauthorized();
    $this->getJson(route('api.v1.contacts.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.engagements.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.testimonials.index'))->assertUnauthorized();
});
