<?php

namespace App\Mcp\Tools;

use App\Enums\ContactStatus;
use App\Enums\EngagementOutcome;
use App\Enums\EngagementStatus;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\EstimatorItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('mark-handled')]
#[Description('Range un message de contact (lu, ou répondu) ou une demande de collaboration (traitée) de get-inbox, quand le développeur dit s\'en être occupé ; pour une demande, enregistre aussi son issue s\'il dit l\'avoir signée (avec le montant) ou perdue. Rien n\'est envoyé au visiteur.')]
class MarkHandledTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'type' => ['required', 'in:contact,engagement'],
            'id' => ['required', 'integer'],
            'replied' => ['nullable', 'boolean'],
            'outcome' => ['nullable', Rule::enum(EngagementOutcome::class)],
            'amount' => ['nullable', 'required_if:outcome,'.EngagementOutcome::Won->value, 'integer', 'min:0', 'max:100000000'],
            'currency' => ['nullable', 'required_if:outcome,'.EngagementOutcome::Won->value, Rule::in(EstimatorItem::CURRENCIES)],
        ]);

        if ($data['type'] === 'contact') {
            $contact = Contact::query()->find($data['id']);

            if ($contact === null) {
                return Response::error('Message introuvable.');
            }

            $contact->update(['status' => ($data['replied'] ?? false) ? ContactStatus::Replied : ContactStatus::Read]);

            return Response::text("Message de {$contact->name} rangé.");
        }

        $engagement = Engagement::query()->find($data['id']);

        if ($engagement === null) {
            return Response::error('Demande introuvable.');
        }

        if (isset($data['outcome'])) {
            $engagement->recordOutcome(EngagementOutcome::from($data['outcome']), $data['amount'] ?? null, $data['currency'] ?? null);

            return Response::text($data['outcome'] === EngagementOutcome::Won->value
                ? "Demande de {$engagement->name} marquée comme signée ({$data['amount']} {$data['currency']})."
                : "Demande de {$engagement->name} marquée comme perdue.");
        }

        $engagement->update(['status' => EngagementStatus::Handled]);

        return Response::text("Demande de {$engagement->name} marquée comme traitée.");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['contact', 'engagement'])->required(),
            'id' => $schema->integer()->description('L\'élément (contacts ou engagements de get-inbox).')->required(),
            'replied' => $schema->boolean()->description('Message de contact : true s\'il a reçu une réponse (sinon simplement lu).'),
            'outcome' => $schema->string()->enum(['won', 'lost'])->description('Demande : seulement si le développeur dit l\'avoir signée (won) ou perdue (lost).'),
            'amount' => $schema->integer()->description('Demande signée : montant signé hors taxes, tel que le développeur le donne.'),
            'currency' => $schema->string()->enum(['CHF', 'EUR', 'USD', 'XOF'])->description('Demande signée : devise du montant.'),
        ];
    }
}
