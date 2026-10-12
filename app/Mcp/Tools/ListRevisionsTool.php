<?php

namespace App\Mcp\Tools;

use App\Models\ContentRevision;
use App\Services\Ai\ContentRevisions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-revisions')]
#[Description('Liste les révisions proposées en attente de relecture (type, contenu, résumé, champs concernés), pour ne pas proposer deux fois la même modification.')]
#[IsReadOnly]
class ListRevisionsTool extends Tool
{
    public function handle(Request $request, ContentRevisions $revisions): Response
    {
        return Response::json(ContentRevision::query()->pending()->latest()->get()
            ->map(fn (ContentRevision $revision): array => [
                'id' => $revision->id,
                'type' => $revision->content_type,
                'content_id' => $revision->content_id,
                'title' => $revisions->title($revision->content_type, $revisions->find($revision->content_type, $revision->content_id)),
                'summary' => $revision->summary,
                'fields' => array_keys([...($revision->changes['fr'] ?? []), ...($revision->changes['en'] ?? [])]),
                'proposed_at' => $revision->created_at->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
