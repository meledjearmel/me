<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('save-content-translation')]
#[Description('Enregistre l\'anglais d\'un brouillon (experience, education, certification, uses_item, skill, service) une fois son français validé par le développeur : les champs traduits remplis en français, et pour une expérience ses réalisations (highlights) dans le même ordre. Le français n\'est pas modifié et rien n\'est publié.')]
#[IsIdempotent]
class SaveContentTranslationTool extends Tool
{
    public function handle(Request $request, ContentTypes $types, ContentDraftWriter $writer): Response
    {
        $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
            'fields' => ['required', 'array'],
        ]);

        try {
            $definition = $types->get($request->get('type'));
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        $content = $definition['model']::query()->find($request->get('id'));

        if ($content?->status !== PublicationStatus::Draft) {
            return Response::error('Seul un brouillon existant peut être traduit par cet outil.');
        }

        $writer->saveTranslation($request->get('type'), $content, $request->get('fields'));

        return Response::text('Traduction anglaise enregistrée. À relire puis publier dans l\'administration : '.route($definition['route'], $content));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_keys(app(ContentTypes::class)->all()))->description('Type de contenu.')->required(),
            'id' => $schema->integer()->description('Identifiant du brouillon.')->required(),
            'fields' => $schema->object()->description('Les champs traduits en anglais (mêmes noms qu\'en français, ex. role, description ; highlights : liste dans le même ordre).')->required(),
        ];
    }
}
