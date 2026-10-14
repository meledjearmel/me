<?php

use App\Mcp\CommandGuide;
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
            ->has('activities.data', 2)
            ->where('activities.data.1.title', 'Lire un projet')
            ->where('activities.data.1.target.label', 'App Station')
            ->where('activities.data.0.success', false)
            ->where('activityOptions.clients', ['ChatGPT', 'Claude Code'])
            ->has('activityOptions.tools', 2));
});

test('the activity is paginated and filtered by client, tool, result, period and search', function () {
    $user = User::factory()->create();
    McpActivity::factory()->count(12)->create(['client' => 'Claude']);
    McpActivity::factory()->failed('Projet introuvable.')->create(['client' => 'Codex', 'name' => 'get-project']);
    McpActivity::factory()->create(['client' => 'Codex', 'name' => 'get-project', 'created_at' => now()->subDays(10)]);

    $page = fn (array $query) => $this->actingAs($user)->get(route('admin.mcp-server.access', $query));

    $page([])->assertInertia(fn ($page) => $page->has('activities.data', 10)->where('activities.total', 14));
    $page(['page' => 2])->assertInertia(fn ($page) => $page->has('activities.data', 4));
    $page(['client' => 'Codex', 'name' => 'get-project'])->assertInertia(fn ($page) => $page->where('activities.total', 2)->where('activityFilters.client', 'Codex'));
    $page(['status' => 'error'])->assertInertia(fn ($page) => $page->where('activities.total', 1));
    $page(['days' => 7, 'name' => 'get-project'])->assertInertia(fn ($page) => $page->where('activities.total', 1));
    // Le titre lisible de l'outil se cherche aussi bien que son nom et que l'erreur.
    $page(['search' => CommandGuide::title('get-project')])->assertInertia(fn ($page) => $page->where('activities.total', 2));
    $page(['search' => 'introuvable'])->assertInertia(fn ($page) => $page->where('activities.total', 1));
});

test('the mobile app reads the activity, filtered by client', function () {
    Sanctum::actingAs(User::factory()->create());
    McpActivity::factory()->create(['client' => 'Claude']);
    McpActivity::factory()->create(['client' => 'Codex']);

    $this->getJson(route('api.v1.mcp-activity.index', ['client' => 'Codex']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.client', 'Codex')
        ->assertJsonPath('total', 1)
        ->assertJsonPath('filters.clients', ['Claude', 'Codex']);
});
