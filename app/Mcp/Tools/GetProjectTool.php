<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Services\Ai\ProjectDraftWriter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-project')]
#[Description('Lit un projet du portfolio (champs en français, identifiants des relations, statut), pour reprendre un brouillon ou s\'inspirer du ton d\'un projet publié.')]
#[IsReadOnly]
class GetProjectTool extends Tool
{
    public function handle(Request $request, ProjectDraftWriter $writer): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        $project = Project::query()->find($request->get('id'));

        if ($project === null) {
            return Response::error('Projet introuvable.');
        }

        return Response::json([
            'id' => $project->id,
            'status' => $project->status->value,
            ...$writer->toInput($project),
            'has_cover' => $project->hasMedia('cover'),
            'gallery_count' => $project->getMedia('gallery')->count(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du projet (voir get-catalog).')->required(),
        ];
    }
}
