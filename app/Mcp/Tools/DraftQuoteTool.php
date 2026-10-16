<?php

namespace App\Mcp\Tools;

use App\Enums\CommercialDocumentType;
use App\Models\Engagement;
use App\Services\CommercialDocuments;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('draft-quote')]
#[Description('Prépare un devis (ou une proforma, une facture) en brouillon à partir d\'une demande (list-estimate-requests ou get-inbox) : le client et les lignes de son estimation, ou les lignes fournies (par exemple ajustées d\'après l\'analyse de l\'IA). Le brouillon n\'a pas de numéro et n\'est jamais envoyé : le développeur le relit, l\'émet et l\'envoie lui-même depuis l\'administration.')]
class DraftQuoteTool extends Tool
{
    public function handle(Request $request, CommercialDocuments $documents): Response
    {
        $data = $request->validate([
            'engagement_id' => ['required', 'integer'],
            'type' => ['nullable', Rule::enum(CommercialDocumentType::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'lines' => ['nullable', 'array', 'min:1', 'max:50'],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0', 'max:100000'],
            'lines.*.unit' => ['required', 'in:hour,day,flat,month,quarter,semester,year'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $engagement = Engagement::query()->find($data['engagement_id']);

        if ($engagement === null) {
            return Response::error('Demande introuvable.');
        }

        $document = $documents->draftFromEngagement($engagement, CommercialDocumentType::tryFrom((string) ($data['type'] ?? '')) ?? CommercialDocumentType::Quote);
        $document->update(array_filter([
            'title' => $data['title'] ?? null,
            'lines' => $data['lines'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]));

        return Response::text(sprintf(
            'Brouillon prêt (%s pour %s, %s %s), à relire, émettre et envoyer depuis l\'administration : %s',
            $document->type->label(),
            $document->client_company ?? $document->client_name,
            number_format($document->total, 0, ',', ' '),
            $document->currency,
            route('admin.commercial-documents.show', $document),
        ));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'engagement_id' => $schema->integer()->description('La demande de collaboration à chiffrer.')->required(),
            'type' => $schema->string()->enum(['quote', 'proforma', 'invoice'])->description('quote par défaut.'),
            'title' => $schema->string()->description('Nom du projet sur le document ; par défaut, le sujet de la demande.'),
            'lines' => $schema->array()->items($schema->object([
                'label' => $schema->string()->required(),
                'description' => $schema->string(),
                'quantity' => $schema->number()->required(),
                'unit' => $schema->string()->enum(['hour', 'day', 'flat', 'month', 'quarter', 'semester', 'year'])->required()->description('hour, day, flat (forfait), ou une période pour un service récurrent (prix par mois, trimestre, semestre ou an).'),
                'unit_price' => $schema->integer()->description('Prix unitaire dans la devise de la demande.')->required(),
            ]))->description('Lignes à la place de celles de l\'estimation ; seulement si le développeur les donne ou les valide.'),
            'notes' => $schema->string()->description('Remarques : ce qui est compris ou non, planning, livrables.'),
        ];
    }
}
