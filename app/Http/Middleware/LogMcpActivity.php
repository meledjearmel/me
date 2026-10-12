<?php

namespace App\Http\Middleware;

use App\Mcp\AiClient;
use App\Models\McpActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Journalise chaque appel d'outil ou de commande du serveur MCP (qui, quoi, sur quel contenu,
 * résultat, durée), sans le contenu envoyé. Un échec du journal ne bloque jamais l'appel.
 */
class LogMcpActivity
{
    /** @var array<string, string> */
    private const array TARGETS = [
        'get-project' => 'project',
        'save-project-draft' => 'project',
        'update-project-draft' => 'project',
        'save-project-translation' => 'project',
        'add-project-image' => 'project',
        'translate-project' => 'project',
        'plan-project-images' => 'project',
        'get-post' => 'post',
        'save-post-draft' => 'post',
        'save-post-translation' => 'post',
        'add-post-image' => 'post',
        'plan-post-images' => 'post',
        'propose-revision' => 'revision',
        'create-technology' => 'technology',
    ];

    /** @var array<string, string> */
    private const array KINDS = ['tools/call' => 'tool', 'prompts/get' => 'prompt'];

    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);
        $response = $next($request);

        $kind = self::KINDS[$request->json('method')] ?? null;

        if ($kind === null || ! is_string($name = $request->json('params.name'))) {
            return $response;
        }

        try {
            $this->record($request, $response, $kind, $name, (int) ((hrtime(true) - $start) / 1_000_000));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function record(Request $request, Response $response, string $kind, string $name, int $duration): void
    {
        $arguments = (array) $request->json('params.arguments', []);
        $reply = json_decode((string) $response->getContent(), true) ?: [];
        $text = (string) data_get($reply, 'result.content.0.text', '');
        $error = data_get($reply, 'error.message') ?? (data_get($reply, 'result.isError') ? $text : null);

        $targetType = self::TARGETS[$name] ?? (in_array($name, ['get-content', 'save-content-draft', 'save-content-translation'], true) ? ($arguments['type'] ?? null) : null);
        $targetId = $arguments['id'] ?? null;

        // Un contenu créé n'a son numéro que dans la réponse (« Brouillon #12 », « identifiant 7 »).
        if ($targetType !== null && $targetId === null && preg_match('/(?:#|identifiant )(\d+)/u', $text, $matches) === 1) {
            $targetId = (int) $matches[1];
        }

        McpActivity::query()->create([
            'client' => AiClient::name($request->user()),
            'kind' => $kind,
            'name' => Str::limit($name, 57),
            'target_type' => is_string($targetType) ? $targetType : null,
            'target_id' => is_numeric($targetId) ? (int) $targetId : null,
            'success' => $response->getStatusCode() < 400 && $error === null,
            'error' => $error === null ? null : Str::limit((string) $error, 490),
            'duration_ms' => $duration,
        ]);
    }
}
