<?php

namespace App\Mcp\Tools;

use App\Services\Ai\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-contents')]
#[Description('Pour un type de contenu (experience, education, certification, uses_item, skill) : la description de ses champs (à utiliser avec save-content-draft) et la liste des contenus existants (identifiant, titre, statut). À appeler avant d\'en créer un, pour éviter les doublons.')]
#[IsReadOnly]
class ListContentsTool extends Tool
{
    public function handle(Request $request, ContentTypes $types): Response
    {
        $request->validate(['type' => ['required', 'string']]);

        try {
            $definition = $types->get($request->get('type'));
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        $title = $definition['title'];

        return Response::json([
            'type' => $request->get('type'),
            'label' => $definition['label'],
            'fields' => $types->documentation($request->get('type')),
            'items' => $definition['model']::query()->orderBy('sort_order')->get()
                ->map(fn (Model $content): array => [
                    'id' => $content->getKey(),
                    'title' => in_array($title, $content->translatable ?? [], true) ? $content->getTranslation($title, 'fr') : $content->getAttribute($title),
                    'status' => $content->status->value,
                ])
                ->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_keys(app(ContentTypes::class)->all()))->description('Type de contenu.')->required(),
        ];
    }
}
