<?php

namespace App\Mcp\Tools;

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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-content')]
#[Description('Lit un contenu (experience, education, certification, uses_item, skill, service) : ses champs en français (fields), l\'anglais déjà traduit (english) et son statut.')]
#[IsReadOnly]
class GetContentTool extends Tool
{
    public function handle(Request $request, ContentTypes $types, ContentDraftWriter $writer): Response
    {
        $request->validate(['type' => ['required', 'string'], 'id' => ['required', 'integer']]);

        try {
            $content = $types->get($request->get('type'))['model']::query()->find($request->get('id'));
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        if ($content === null) {
            return Response::error('Contenu introuvable.');
        }

        return Response::json([
            'type' => $request->get('type'),
            'id' => $content->getKey(),
            'status' => $content->status->value,
            'fields' => $writer->toInput($request->get('type'), $content),
            'english' => $writer->english($request->get('type'), $content),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_keys(app(ContentTypes::class)->all()))->description('Type de contenu.')->required(),
            'id' => $schema->integer()->description('Identifiant du contenu (list-contents).')->required(),
        ];
    }
}
