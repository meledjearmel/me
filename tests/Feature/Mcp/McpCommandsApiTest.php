<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('the MCP commands require authentication', function () {
    $this->getJson('/api/v1/mcp-commands')->assertUnauthorized();
});

test('the MCP commands list the server address, prompts and tools', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/mcp-commands')
        ->assertOk()
        ->assertJsonPath('data.server_url', url('/mcp/portfolio'))
        ->assertJsonCount(6, 'data.prompts')
        ->assertJsonCount(22, 'data.tools');

    foreach ($response->json('data.prompts') as $prompt) {
        expect($prompt['kind'])->toBe('prompt')
            ->and($prompt['requests'])->not->toBeEmpty();
    }
});
