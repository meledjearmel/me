<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Services\Ai\ContentDraftWriter;
use App\Services\Ai\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-content-draft')]
#[Description('Crée en brouillon (sans id) ou modifie (avec id, seulement les champs envoyés) une expérience, une formation, une certification, une distinction, un élément de la page « Uses », une compétence ou une offre de la page « Services », en français. Les champs de chaque type sont décrits par list-contents. Le développeur relit, fait traduire et publie lui-même ; un contenu publié n\'est jamais modifié ici. N\'invente rien : dates, organismes et chiffres viennent de la source ou du développeur.')]
class SaveContentDraftTool extends Tool
{
    use NamesAiClient;

    public function handle(Request $request, ContentTypes $types, ContentDraftWriter $writer): Response
    {
        $request->validate([
            'type' => ['required', 'string'],
            'id' => ['nullable', 'integer'],
            'fields' => ['required', 'array'],
        ]);

        $type = $request->get('type');

        try {
            $definition = $types->get($type);
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        $content = $request->get('id') !== null
            ? $definition['model']::query()->find($request->get('id'))
            : new $definition['model'];

        if ($request->get('id') !== null && $content?->status !== PublicationStatus::Draft) {
            return Response::error('Seul un brouillon existant peut être modifié.');
        }

        $current = $content->exists ? $writer->toInput($type, $content) : [];
        $changes = collect($request->get('fields'))->only(array_keys($types->documentation($type)))->all();

        if (! $content->exists) {
            $content->drafted_by = $this->aiClient($request);
        }

        $removed = $writer->save($type, $content, [...$current, ...$changes]);

        return Response::text(implode("\n", array_filter([
            "Brouillon #{$content->getKey()} enregistré ({$definition['label']}).",
            'À relire, traduire et publier dans l\'administration : '.route($definition['route'], $content),
            $removed !== [] ? 'Mentions retirées (élément inconnu ou non publié, remplacé par son libellé) : '.implode(', ', $removed) : null,
        ])));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_keys(app(ContentTypes::class)->all()))->description('Type de contenu.')->required(),
            'id' => $schema->integer()->description('Brouillon existant à modifier ; absent pour en créer un.'),
            'fields' => $schema->object()->description('Les champs à enregistrer, en français, tels que list-contents les décrit pour ce type.')->required(),
        ];
    }
}
