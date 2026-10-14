<?php

namespace App\Mcp\Tools;

use App\Enums\SyndicationPlatform;
use App\Models\Post;
use App\Models\PostSyndication;
use App\Services\Syndication\PostSyndicator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('syndicate-post')]
#[Description('Republie un article déjà publié sur le site vers dev.to ou Hashnode (ou met à jour sa version republiée), avec un lien canonique vers le site. Publie réellement en ligne : ne l\'appelle que si le développeur le demande explicitement pour cet article et cette plateforme. La plateforme doit être configurée dans l\'administration (Blog, Republication).')]
#[IsOpenWorld]
class SyndicatePostTool extends Tool
{
    public function handle(Request $request, PostSyndicator $syndicator): Response
    {
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
            'platform' => ['required', Rule::enum(SyndicationPlatform::class)],
        ]);

        $post = Post::query()->with('tags')->find($data['post_id']);

        if ($post === null) {
            return Response::error('Article introuvable.');
        }

        $syndication = $syndicator->syndicate($post, SyndicationPlatform::from($data['platform']));

        return $syndication->status === PostSyndication::PUBLISHED
            ? Response::text("Article publié sur {$syndication->platform->label()} : {$syndication->external_url}")
            : Response::error((string) $syndication->error);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()->description('L\'article publié (voir get-catalog).')->required(),
            'platform' => $schema->string()->enum(['devto', 'hashnode'])->required(),
        ];
    }
}
