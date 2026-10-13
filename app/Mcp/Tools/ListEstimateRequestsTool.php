<?php

namespace App\Mcp\Tools;

use App\Enums\EngagementStatus;
use App\Models\Engagement;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-estimate-requests')]
#[Description('Liste les demandes reçues par l\'estimateur de projet, les plus récentes d\'abord : choix du visiteur, estimation affichée, message, délai souhaité, et l\'analyse de l\'IA si elle existe. Les coordonnées du visiteur ne sont pas données.')]
#[IsReadOnly]
class ListEstimateRequestsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'only_new' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $engagements = Engagement::query()
            ->whereNotNull('estimate')
            ->when($data['id'] ?? null, fn ($query, int $id) => $query->whereKey($id))
            ->when($data['only_new'] ?? false, fn ($query) => $query->where('status', EngagementStatus::New))
            ->latest()
            ->limit($data['limit'] ?? 10)
            ->get();

        return Response::json($engagements->map(fn (Engagement $engagement): array => [
            'id' => $engagement->id,
            'status' => $engagement->status->value,
            'received_at' => $engagement->created_at?->toIso8601String(),
            'company' => $engagement->company,
            'estimate' => $engagement->estimate,
            'estimate_label' => $engagement->estimate_label,
            'timeline' => $engagement->timeline,
            'message' => $engagement->message,
            'ai_analysis' => $engagement->ai_analysis,
            'admin_url' => route('admin.engagements.show', $engagement),
        ])->all());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Une seule demande, par son identifiant.'),
            'only_new' => $schema->boolean()->description('Seulement les demandes pas encore traitées.'),
            'limit' => $schema->integer()->description('Nombre de demandes (10 par défaut, 50 max.).'),
        ];
    }
}
