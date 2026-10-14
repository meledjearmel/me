<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use App\Services\Syndication\SocialKit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-post-social-texts')]
#[Description('Enregistre le kit de partage d\'un article (lis-le d\'abord avec get-post) : les textes qui l\'annoncent sur LinkedIn, X, Facebook et en statut WhatsApp, dans une langue. N\'écris pas l\'adresse de l\'article : elle est ajoutée au partage. Rien n\'est publié : le développeur partage lui-même depuis la fiche de l\'article (un clic par réseau). Renvoie les liens de partage déjà remplis.')]
class SavePostSocialTextsTool extends Tool
{
    public function handle(Request $request, SocialKit $kit): Response
    {
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'linkedin' => ['nullable', 'string', 'max:2800'],
            'x' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:2000'],
            'whatsapp' => ['nullable', 'string', 'max:300'],
        ]);

        $post = Post::query()->find($data['post_id']);

        if ($post === null) {
            return Response::error('Article introuvable.');
        }

        $texts = $kit->normalize($data);

        if ($texts === null) {
            return Response::error('Donne au moins un texte (linkedin, x, facebook ou whatsapp).');
        }

        $kit->save($post, $data['locale'], $texts);

        return Response::json([...$kit->kit($post->refresh(), $data['locale']), 'admin_url' => route('admin.posts.show', $post)]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()->description('L\'article (voir get-catalog).')->required(),
            'locale' => $schema->string()->enum(['fr', 'en'])->description('Langue des textes.')->required(),
            'linkedin' => $schema->string()->description('3 à 6 phrases courtes en 2 ou 3 paragraphes, une accroche en première ligne, 3 à 5 hashtags sur la dernière ligne.'),
            'x' => $schema->string()->description('200 caractères au plus, une idée forte, 1 ou 2 hashtags.'),
            'facebook' => $schema->string()->description('2 à 4 phrases, ton conversationnel.'),
            'whatsapp' => $schema->string()->description('Statut très court : 1 ou 2 phrases, 120 caractères au plus.'),
        ];
    }
}
