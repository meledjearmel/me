<?php

namespace App\Mcp\Tools;

use App\Enums\CommentStatus;
use App\Models\PostComment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('moderate-comment')]
#[Description('Publie (approved) ou masque (rejected) un commentaire du blog (voir get-inbox). Un commentaire approuvé s\'affiche aussitôt sous l\'article : ne l\'appelle que sur demande explicite du développeur pour ce commentaire.')]
class ModerateCommentTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', Rule::in([CommentStatus::Approved->value, CommentStatus::Rejected->value])],
        ]);

        $comment = PostComment::query()->find($data['id']);

        if ($comment === null) {
            return Response::error('Commentaire introuvable.');
        }

        $comment->update(['status' => $data['status']]);

        return Response::text(($data['status'] === CommentStatus::Approved->value ? 'Commentaire publié' : 'Commentaire masqué')
            ." ({$comment->author_name}).");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Le commentaire (comments de get-inbox).')->required(),
            'status' => $schema->string()->enum(['approved', 'rejected'])->required(),
        ];
    }
}
