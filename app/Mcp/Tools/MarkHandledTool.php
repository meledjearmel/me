<?php

namespace App\Mcp\Tools;

use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Models\Contact;
use App\Models\Engagement;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('mark-handled')]
#[Description('Range un message de contact (lu, ou répondu) ou une demande de collaboration (traitée) de get-inbox, quand le développeur dit s\'en être occupé. Rien n\'est envoyé au visiteur.')]
class MarkHandledTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'type' => ['required', 'in:contact,engagement'],
            'id' => ['required', 'integer'],
            'replied' => ['nullable', 'boolean'],
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
        ];
    }
}
