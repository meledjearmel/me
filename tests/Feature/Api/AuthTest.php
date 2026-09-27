<?php

use App\Models\User;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;

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

test('la connexion sur un compte à double authentification renvoie un défi', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->assertOk()
        ->assertJsonPath('two_factor', true)
        ->assertJsonStructure(['challenge'])
        ->assertJsonMissing(['token']);

    expect($user->tokens()->count())->toBe(0);
});

test('un code de vérification valide termine la connexion', function () {
    $secret = app(Google2FA::class)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $challenge = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => $challenge,
        'device_name' => 'iPhone',
        'code' => app(Google2FA::class)->getCurrentOtp($secret),
    ])->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect($user->tokens()->count())->toBe(1);
});

test('un code de vérification invalide échoue', function () {
    $user = User::factory()->withTwoFactor()->create();

    $challenge = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => $challenge,
        'device_name' => 'iPhone',
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');

    expect($user->tokens()->count())->toBe(0);
});

test('un défi inconnu ou expiré échoue', function () {
    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => 'inconnu',
        'device_name' => 'iPhone',
        'code' => '123456',
    ])->assertUnprocessable()->assertJsonValidationErrors('challenge');
});

test('sans code ni code de secours, le défi est refusé', function () {
    $user = User::factory()->withTwoFactor()->create();

    $challenge = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => $challenge,
        'device_name' => 'iPhone',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

test('un code de secours valide termine la connexion et ne peut pas être réutilisé', function () {
    $user = User::factory()->withTwoFactor()->create();

    $challenge = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => $challenge,
        'device_name' => 'iPhone',
        'recovery_code' => 'recovery-code-1',
    ])->assertOk()->assertJsonStructure(['token']);

    expect($user->fresh()->recoveryCodes())->not->toContain('recovery-code-1');

    $secondChallenge = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), [
        'challenge' => $secondChallenge,
        'device_name' => 'iPhone',
        'recovery_code' => 'recovery-code-1',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
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
    $this->getJson(route('api.v1.trash.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.contacts.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.engagements.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.testimonials.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.music-genres.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.tracks.index'))->assertUnauthorized();
    $this->postJson(route('api.v1.push-tokens.store'))->assertUnauthorized();
});
