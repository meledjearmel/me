<?php

use App\Models\OAuthUser;
use App\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

test('connectors discover the authorization server from the mcp endpoint', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp/portfolio')
        ->assertOk()
        ->assertJsonPath('authorization_servers.0', url('/'));

    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('registration_endpoint', url('oauth/register'))
        ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'));

    $this->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/portfolio').'"');
});

test('only claude, chatgpt and local clients can register', function (string $redirect, bool $accepted) {
    $response = $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => [$redirect]]);

    $accepted ? $response->assertSuccessful()->assertJsonStructure(['client_id']) : $response->assertStatus(400);
})->with([
    'claude' => ['https://claude.ai/api/mcp/auth_callback', true],
    'chatgpt' => ['https://chatgpt.com/connector_platform_oauth_redirect', true],
    'claude code' => ['http://localhost:33418/callback', true],
    'look-alike domain' => ['https://claude.ai.attaquant.com/callback', false],
    'other site' => ['https://attaquant.com/callback', false],
]);

test('the logged-in admin sees the french authorization screen', function () {
    $this->withoutVite();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Claude', ['https://claude.ai/api/mcp/auth_callback'], confidential: false);

    $this->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'abc',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'abc',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertOk()->assertSee('Autoriser Claude')->assertSee("Rien n'est publié", false)->assertSee('Lire le portfolio');
});

test('an oauth token with the mcp scope reaches the tools', function () {
    Passport::actingAs(OAuthUser::query()->findOrFail(User::factory()->create()->id), ['mcp:use']);

    $this->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertOk()
        ->assertSee('save-project-draft');
});

test('an oauth token without the mcp scope is refused', function () {
    Passport::actingAs(OAuthUser::query()->findOrFail(User::factory()->create()->id), ['other']);

    $this->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertForbidden();
});

test('a connector is listed and disconnected from the admin', function () {
    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('ChatGPT', ['https://chatgpt.com/cb'], confidential: false);
    $token = Passport::token()->newQuery()->forceCreate([
        'id' => str_repeat('a', 80),
        'user_id' => $user->id,
        'client_id' => $client->getKey(),
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)->get(route('admin.mcp-server.access'))
        ->assertInertia(fn ($page) => $page->where('connectors.0.name', 'ChatGPT'));

    $this->actingAs($user)->delete(route('admin.mcp-server.connectors.destroy', $client->getKey()))->assertRedirect();

    expect($token->fresh()->revoked)->toBeTrue();
});
