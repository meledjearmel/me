<?php

use App\Models\McpActivity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

function mcpCall(string $token, string $method, array $params = []): TestResponse
{
    app('auth')->forgetGuards();

    return test()->withToken($token)->postJson('/mcp/portfolio', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
}

test('each tool call is logged with its client, target and result, without the content sent', function () {
    $token = User::factory()->create()->createToken('Codex', ['mcp'])->plainTextToken;

    mcpCall($token, 'tools/list');
    mcpCall($token, 'tools/call', ['name' => 'save-project-draft', 'arguments' => [
        'title' => 'Gestion de stock', 'slug' => 'gestion-de-stock', 'context' => 'Contexte secret.',
        'realization' => 'Réalisation.', 'result' => 'Résultat.',
    ]])->assertOk();
    mcpCall($token, 'tools/call', ['name' => 'get-project', 'arguments' => ['id' => 999]]);

    expect(McpActivity::query()->count())->toBe(2);

    $draft = Project::query()->where('slug', 'gestion-de-stock')->firstOrFail();
    $created = McpActivity::query()->where('name', 'save-project-draft')->sole();
    expect($created->client)->toBe('Codex')
        ->and($created->kind)->toBe('tool')
        ->and($created->target_type)->toBe('project')
        ->and($created->target_id)->toBe($draft->id)
        ->and($created->success)->toBeTrue()
        ->and(json_encode($created->toArray()))->not->toContain('Contexte secret');

    $failed = McpActivity::query()->where('name', 'get-project')->sole();
    expect($failed->success)->toBeFalse()
        ->and($failed->error)->toBe('Projet introuvable.')
        ->and($failed->target_id)->toBe(999);
});

test('prompts are logged too', function () {
    $token = User::factory()->create()->createToken('Claude Code', ['mcp'])->plainTextToken;

    mcpCall($token, 'prompts/get', ['name' => 'write-post', 'arguments' => ['subject' => 'Les files']])->assertOk();

    expect(McpActivity::query()->sole())->kind->toBe('prompt')->name->toBe('write-post');
});

test('the log keeps 90 days', function () {
    McpActivity::factory()->create(['created_at' => now()->subDays(91)]);
    McpActivity::factory()->create(['created_at' => now()->subDays(89)]);

    Artisan::call('model:prune', ['--model' => [McpActivity::class]]);

    expect(McpActivity::query()->count())->toBe(1);
});

test('the access page loads the activity with links and each client last call', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station']]);
    McpActivity::factory()->create(['client' => 'Claude Code', 'name' => 'get-project', 'target_type' => 'project', 'target_id' => $project->id]);
    McpActivity::factory()->failed()->create(['client' => 'ChatGPT']);

    $this->actingAs($user)->get(route('admin.mcp-server.access'))
        ->assertInertia(fn ($page) => $page->component('admin/mcp-server/access')
            ->missing('activities')
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('activities', 2)
                ->where('activities.1.title', 'Lire un projet')
                ->where('activities.1.target.label', 'App Station')
                ->where('activities.0.success', false)));
});

test('the mobile app reads the activity, filtered by client', function () {
    Sanctum::actingAs(User::factory()->create());
    McpActivity::factory()->create(['client' => 'Claude']);
    McpActivity::factory()->create(['client' => 'Codex']);

    $this->getJson(route('api.v1.mcp-activity.index', ['client' => 'Codex']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.client', 'Codex');
});
