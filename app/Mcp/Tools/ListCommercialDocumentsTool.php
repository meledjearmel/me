<?php

namespace App\Mcp\Tools;

use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Models\CommercialDocument;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-commercial-documents')]
#[Description('Liste les devis, factures proforma et factures, les plus récents d\'abord : numéro (ADEV-DEV, ADEV-PRO, ADEV-FAC), statut, client (sans email), projet, lignes, total, acompte, dates et demande liée. Pour suivre ce qui est envoyé, accepté ou à encaisser, et ce qui est à relancer (la relance elle-même se fait depuis l\'administration).')]
#[IsReadOnly]
class ListCommercialDocumentsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(CommercialDocumentType::class)],
            'status' => ['nullable', Rule::enum(CommercialDocumentStatus::class)],
            'to_remind' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $documents = CommercialDocument::query()
            ->when($data['id'] ?? null, fn ($query, int $id) => $query->whereKey($id))
            ->when($data['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($data['to_remind'] ?? false, fn ($query) => $query->whereNotNull('reminder_due_at'))
            ->latest()
            ->limit($data['limit'] ?? 10)
            ->get();

        return Response::json($documents->map(fn (CommercialDocument $document): array => [
            'id' => $document->id,
            'type' => $document->type->value,
            'status' => $document->status->value,
            'number' => $document->number,
            'client' => $document->client_company ?? $document->client_name,
            'title' => $document->title,
            'currency' => $document->currency,
            'lines' => $document->lines,
            'total' => $document->total,
            'deposit_percent' => $document->deposit_percent,
            'issued_at' => $document->issued_at?->toDateString(),
            'valid_until' => $document->valid_until?->toDateString(),
            'due_at' => $document->due_at?->toDateString(),
            'sent_at' => $document->sent_at?->toIso8601String(),
            'reminders_count' => $document->reminders_count,
            'reminder_suggested' => $document->reminder_due_at !== null,
            'engagement_id' => $document->engagement_id,
            'admin_url' => route('admin.commercial-documents.show', $document),
        ])->all());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Un seul document, par son identifiant.'),
            'type' => $schema->string()->enum(['quote', 'proforma', 'invoice'])->description('Seulement les devis, les proformas ou les factures.'),
            'status' => $schema->string()->enum(['draft', 'issued', 'accepted', 'declined', 'paid', 'cancelled'])->description('Seulement ce statut (issued : émis, en attente de réponse ou de paiement).'),
            'to_remind' => $schema->boolean()->description('Seulement les documents à relancer (devis sans réponse, factures en retard).'),
            'limit' => $schema->integer()->description('Nombre de documents (10 par défaut, 50 max.).'),
        ];
    }
}
