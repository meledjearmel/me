<?php

use App\Models\User;

test('guests cannot manage mcp tokens', function () {
    $this->get(route('admin.mcp-server.access'))->assertRedirect(route('login'));
});

test('a created mcp token is limited to the mcp server', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.mcp-server.tokens.store'), ['name' => 'Claude Code'])
        ->assertRedirect();

    $token = $user->tokens()->sole();
    expect($token->name)->toBe('Claude Code')->and($token->abilities)->toBe(['mcp']);
});

test('an mcp token reaches the mcp server but is refused by the api', function () {
    $plain = User::factory()->create()->createToken('Codex', ['mcp'])->plainTextToken;

    $this->withToken($plain)->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertOk()
        ->assertSee('save-project-draft');

    $this->app['auth']->forgetGuards();

    $this->withToken($plain)->getJson(route('api.v1.auth.me'))->assertForbidden();
});

test('a token without the mcp ability cannot reach the mcp server', function () {
    $plain = User::factory()->create()->createToken('iPhone', ['read'])->plainTextToken;

    $this->withToken($plain)->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertForbidden();
});

test('the list only shows mcp tokens, which can be revoked', function () {
    $user = User::factory()->create();
    $mcp = $user->createToken('Claude Code', ['mcp'])->accessToken;
    $user->createToken('iPhone');

    $this->actingAs($user)->get(route('admin.mcp-server.access'))
        ->assertInertia(fn ($page) => $page->component('admin/mcp-server/access')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Claude Code'));

    $this->actingAs($user)->delete(route('admin.mcp-server.tokens.destroy', $mcp->id))->assertRedirect();

    expect($user->tokens()->pluck('name')->all())->toBe(['iPhone']);
});

test('the mcp server page lists every command with its guide', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.mcp-server.index'))
        ->assertInertia(fn ($page) => $page->component('admin/mcp-server/index')
            ->where('serverUrl', url('/mcp/portfolio'))
            ->has('prompts', 7)
            ->has('tools', 46)
            ->where('prompts', fn ($prompts) => collect($prompts)->every(fn ($prompt) => filled($prompt['usage']) && filled($prompt['example']) && filled($prompt['requests'])))
            ->where('tools', fn ($tools) => collect($tools)->every(fn ($tool) => filled($tool['usage']) && $tool['requests'] === [$tool['example']])));
});
