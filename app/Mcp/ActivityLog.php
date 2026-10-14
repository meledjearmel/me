<?php

namespace App\Mcp;

use App\Models\ContentRevision;
use App\Models\McpActivity;
use App\Models\Technology;
use App\Services\Ai\ContentRevisions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
     * Les appels filtrés, du plus récent au plus ancien. La recherche porte sur l'outil (nom
     * technique ou titre lisible) et le message d'erreur ; « status » vaut `success` ou `error`,
     * « days » limite aux N derniers jours.
     *
     * @param  array{search?: string|null, client?: string|null, name?: string|null, kind?: string|null, status?: string|null, days?: int|string|null}  $filters
     * @return Builder<McpActivity>
     */
    public function query(array $filters): Builder
    {
        $term = trim((string) ($filters['search'] ?? ''));
        $days = (int) ($filters['days'] ?? 0);

        return McpActivity::query()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $titled = $this->names()->filter(fn (string $name): bool => Str::contains(CommandGuide::title($name), $term, ignoreCase: true));

                $query->where(fn (Builder $group) => $group
                    ->where('name', 'like', $like)
                    ->orWhere('error', 'like', $like)
                    ->orWhereIn('name', $titled->all()));
            })
            ->when($filters['client'] ?? null, fn (Builder $query, string $client) => $query->where('client', $client))
            ->when($filters['name'] ?? null, fn (Builder $query, string $name) => $query->where('name', $name))
            ->when($filters['kind'] ?? null, fn (Builder $query, string $kind) => $query->where('kind', $kind))
            ->when(($filters['status'] ?? null) === 'success', fn (Builder $query) => $query->where('success', true))
            ->when(($filters['status'] ?? null) === 'error', fn (Builder $query) => $query->where('success', false))
            ->when($days > 0, fn (Builder $query) => $query->where('created_at', '>=', now()->subDays($days)))
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * Les choix des filtres : clients et outils présents dans le journal.
     *
     * @return array{clients: list<string>, tools: list<array{value: string, label: string}>}
     */
    public function filterOptions(): array
    {
        return [
            'clients' => McpActivity::query()->distinct()->orderBy('client')->pluck('client')->all(),
            'tools' => $this->names()
                ->map(fn (string $name): array => ['value' => $name, 'label' => CommandGuide::title($name)])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        ];
    }

    /** @return Collection<int, string> */
    private function names(): Collection
    {
        return McpActivity::query()->distinct()->pluck('name');
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
