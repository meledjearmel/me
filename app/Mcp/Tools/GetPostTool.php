<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use App\Services\Ai\PostDraftWriter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-post')]
#[Description('Lit un article du blog en français (HTML du contenu, tags, série, statut), pour reprendre un brouillon ou s\'inspirer du ton d\'un article publié. Les mentions y sont des <span data-type="mention">.')]
#[IsReadOnly]
class GetPostTool extends Tool
{
    public function handle(Request $request, PostDraftWriter $writer): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        $post = Post::query()->find($request->get('id'));

        if ($post === null) {
            return Response::error('Article introuvable.');
        }

        return Response::json([
            'id' => $post->id,
            'status' => $post->status->value,
            ...$writer->toInput($post),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant de l\'article (voir get-catalog).')->required(),
        ];
    }
}
