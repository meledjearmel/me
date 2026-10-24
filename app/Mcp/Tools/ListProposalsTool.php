<?php

namespace App\Mcp\Tools;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-proposals')]
#[Description('Liste les propositions freelance du développeur (Upwork, Malt…), privées, avec leur texte : les meilleurs exemples d\'abord (mission obtenue, entretien, vue…), puis les plus récentes. Sers-t\'en comme modèles de style avant d\'en rédiger une. Avec un id : la proposition complète, annonce et notes comprises.')]
#[IsReadOnly]
class ListProposalsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'platform' => ['nullable', Rule::in(Proposal::PLATFORMS)],
            'locale' => ['nullable', Rule::in(['fr', 'en'])],
            'status' => ['nullable', Rule::enum(ProposalStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        if (isset($data['id'])) {
            $proposal = Proposal::query()->find((int) $data['id']);

            if ($proposal === null) {
                return Response::error('Proposition introuvable.');
            }

            return Response::json([
                ...$this->summary($proposal),
                'job_url' => $proposal->job_url,
                'job_posting' => $proposal->job_posting,
                'notes' => $proposal->notes,
                'drafted_by' => $proposal->drafted_by,
            ]);
        }

        $proposals = Proposal::query()
            ->when($data['platform'] ?? null, fn (Builder $query, string $platform) => $query->where('platform', $platform))
            ->when($data['locale'] ?? null, fn (Builder $query, string $locale) => $query->where('locale', $locale))
            ->when($data['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($data['search'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $query) => $query
                ->where('job_title', 'like', "%{$term}%")
                ->orWhere('job_posting', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")))
            ->bestFirst()
            ->limit($data['limit'] ?? 8)
            ->get();

        if ($proposals->isEmpty()) {
            return Response::text('Aucune proposition enregistrée pour ces critères : rédige d\'après les règles seules.');
        }

        return Response::json($proposals->map(fn (Proposal $proposal): array => $this->summary($proposal))->all());
    }

    /** @return array<string, mixed> */
    private function summary(Proposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'platform' => $proposal->platform,
            'locale' => $proposal->locale,
            'status' => $proposal->status->value,
            'job_title' => $proposal->job_title,
            'client_name' => $proposal->client_name,
            'body' => $proposal->body,
            'screening_answers' => $proposal->screening_answers,
            'bid' => $proposal->bid,
            'sent_at' => $proposal->sent_at?->toIso8601String(),
            'admin_url' => route('admin.proposals.edit', $proposal),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Une seule proposition, en entier (annonce et notes comprises).'),
            'platform' => $schema->string()->enum(Proposal::PLATFORMS)->description('Seulement cette plateforme.'),
            'locale' => $schema->string()->enum(['fr', 'en'])->description('Seulement cette langue.'),
            'status' => $schema->string()->enum(array_column(ProposalStatus::cases(), 'value'))->description('Seulement ce statut.'),
            'search' => $schema->string()->description('Mot-clé cherché dans le titre, l\'annonce et la proposition (ex. « Laravel »), pour trouver des offres proches.'),
            'limit' => $schema->integer()->description('Nombre de propositions (8 par défaut, 30 max.).'),
        ];
    }
}
