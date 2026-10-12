<?php

namespace App\Mcp;

use App\Models\ContentRevision;
use App\Models\McpActivity;
use App\Models\Technology;
use App\Services\Ai\ContentRevisions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le journal d'activité du serveur MCP prêt à afficher (administration, API) : titre de
 * l'outil, et contenu visé avec son lien quand il existe encore.
 */
class ActivityLog
{
    /** @var array<string, array{label: string, url: string|null}|null> */
    private array $targets = [];

    public function __construct(private readonly ContentRevisions $revisions) {}

    /**
     * Les appels les plus récents.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function latest(int $limit = 200, ?string $client = null): Collection
    {
        return McpActivity::query()
            ->when($client, fn ($query) => $query->where('client', $client))
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (McpActivity $activity): array => $this->present($activity));
    }

    /**
     * Dernier appel de chaque client, indexé par nom de client.
     *
     * @return array<string, string>
     */
    public function lastCallByClient(): array
    {
        return McpActivity::query()
            ->selectRaw('client, max(created_at) as last_call_at')
            ->groupBy('client')
            ->pluck('last_call_at', 'client')
            ->map(fn (string $date): string => Carbon::parse($date)->toIso8601String())
            ->all();
    }

    /** @return array<string, mixed> */
    public function present(McpActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'client' => $activity->client,
            'kind' => $activity->kind,
            'name' => $activity->name,
            'title' => CommandGuide::title($activity->name),
            'target' => $this->target($activity->target_type, $activity->target_id),
            'success' => $activity->success,
            'error' => $activity->error,
            'duration_ms' => $activity->duration_ms,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /** @return array{label: string, url: string|null}|null */
    private function target(?string $type, ?int $id): ?array
    {
        if ($type === null) {
            return null;
        }

        return $this->targets["{$type}:{$id}"] ??= match (true) {
            $type === 'revision' => ($revision = ContentRevision::query()->find($id)) === null
                ? ['label' => "Révision #{$id} (supprimée)", 'url' => null]
                : ['label' => "Révision #{$id}", 'url' => route('admin.revisions.show', $revision)],
            $type === 'technology' => ($technology = Technology::query()->find($id)) === null
                ? null
                : ['label' => $technology->name, 'url' => route('admin.technologies.edit', $technology)],
            in_array($type, $this->revisions->typeNames(), true) => (function () use ($type, $id): ?array {
                $content = $this->revisions->find($type, $id);

                return $content === null ? null : [
                    'label' => $this->revisions->title($type, $content),
                    'url' => $this->revisions->editUrl($type, $content),
                ];
            })(),
            default => null,
        };
    }
}
