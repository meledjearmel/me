<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
use App\Models\Post;
use App\Services\Ai\PostDraftWriter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Enregistre la traduction anglaise d\'un brouillon d\'article, une fois son français validé par le développeur. Le français n\'est pas modifié et l\'article n\'est pas publié.')]
#[IsIdempotent]
class SavePostTranslationTool extends Tool
{
    public function handle(Request $request, PostDraftWriter $writer): Response
    {
        $post = Post::query()->find($request->get('id'));

        if ($post?->status !== PublicationStatus::Draft) {
            return Response::error('Seul un brouillon d\'article existant peut être traduit par cet outil.');
        }

        $writer->saveTranslation($post, collect($request->all())->only(['title', 'excerpt', 'body'])->all());

        return Response::text("Traduction anglaise du brouillon d'article #{$post->id} enregistrée.\nAperçu anglais : {$post->previewUrl('en')}\nÀ publier dans l'administration : ".route('admin.posts.edit', $post));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du brouillon.')->required(),
            'title' => $schema->string()->description('Titre en anglais.')->required(),
            'excerpt' => $schema->string()->description('Résumé en anglais (300 max.).'),
            'body' => $schema->string()->description('Contenu en anglais, en gardant exactement la structure HTML du français. Les mentions s\'écrivent en jeton @[Label](type:id) ; garder le type et l\'identifiant.')->required(),
        ];
    }
}
