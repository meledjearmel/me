<?php

namespace App\Mcp\Tools;

use App\Enums\ProposalStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Models\Proposal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-proposal')]
#[Description('Seulement après que le développeur a validé la proposition dans la discussion : enregistre une proposition freelance (sans id, en brouillon), ou modifie une proposition existante (avec id, seulement les champs envoyés) : texte final réellement envoyé, réponses aux questions, statut (sent, viewed, interview, hired, declined). Privée : rien n\'est publié ni envoyé au client, le développeur la colle lui-même sur la plateforme.')]
class SaveProposalTool extends Tool
{
    use NamesAiClient;

    public function handle(Request $request): Response
    {
        $creating = $request->get('id') === null;
        $required = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'platform' => [$required, Rule::in(Proposal::PLATFORMS)],
            'locale' => [$required, Rule::in(['fr', 'en'])],
            'job_title' => [$required, 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'job_url' => ['nullable', 'url:http,https', 'max:2000'],
            'job_posting' => ['nullable', 'string', 'max:20000'],
            'body' => [$required, 'string', 'max:10000'],
            'screening_answers' => ['nullable', 'string', 'max:10000'],
            'bid' => ['nullable', 'string', 'max:120'],
            'connects' => ['nullable', 'integer', 'min:0', 'max:500'],
            'status' => ['sometimes', Rule::enum(ProposalStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $proposal = $creating
            ? new Proposal(['status' => ProposalStatus::Draft, 'drafted_by' => $this->aiClient($request)])
            : Proposal::query()->find($data['id']);

        if ($proposal === null) {
            return Response::error('Proposition introuvable.');
        }

        $proposal->fill(collect($data)->except('id')->all())->save();

        return Response::text(($creating ? 'Proposition enregistrée en brouillon' : 'Proposition mise à jour')
            ." (#{$proposal->id}, statut {$proposal->status->value}) : ".route('admin.proposals.edit', $proposal)
            .($creating ? '. Une fois envoyée, passe-la en « sent » et colle la version finale si elle a été retouchée.' : '.'));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Proposition à modifier ; sans id, une nouvelle est créée en brouillon.'),
            'platform' => $schema->string()->enum(Proposal::PLATFORMS)->description('Plateforme de l\'offre.'),
            'locale' => $schema->string()->enum(['fr', 'en'])->description('Langue de la proposition.'),
            'job_title' => $schema->string()->description('Titre de l\'offre.'),
            'client_name' => $schema->string()->description('Prénom ou nom du client, s\'il est connu.'),
            'job_url' => $schema->string()->description('Lien de l\'offre.'),
            'job_posting' => $schema->string()->description('Texte complet de l\'annonce, questions comprises.'),
            'body' => $schema->string()->description('La proposition, en texte brut.'),
            'screening_answers' => $schema->string()->description('Réponses aux questions de l\'offre, chacune précédée de sa question.'),
            'bid' => $schema->string()->description('Tarif ou montant proposé, en texte libre (ex. « 40 $/h », « 1 200 € au forfait »).'),
            'connects' => $schema->integer()->description('Connects Upwork dépensés.'),
            'status' => $schema->string()->enum(array_column(ProposalStatus::cases(), 'value'))->description('draft, sent, viewed, interview, hired ou declined.'),
            'notes' => $schema->string()->description('Notes privées (retour du client, ce qui a marché…).'),
        ];
    }
}
