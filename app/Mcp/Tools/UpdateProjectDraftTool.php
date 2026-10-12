<?php

namespace App\Mcp\Tools;

use App\Enums\ProjectStatus;
use App\Mcp\Concerns\DescribesProjectDraft;
use App\Models\Project;
use App\Services\Ai\ProjectDraftWriter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update-project-draft')]
#[Description('Modifie seulement les champs envoyés d\'un brouillon de projet (en français) ; les autres restent tels quels, l\'anglais déjà traduit aussi. Une liste envoyée (choix techniques, chiffres clés, technologies…) remplace la liste entière : la relire avec get-project avant. Pour vider un champ texte, envoyer null.')]
#[IsIdempotent]
class UpdateProjectDraftTool extends Tool
{
    use DescribesProjectDraft;

    public function handle(Request $request, ProjectDraftWriter $writer): Response
    {
        $request->validate(['id' => ['required', 'integer']]);
        $project = Project::query()->find($request->get('id'));

        if ($project?->status !== ProjectStatus::Draft) {
            return Response::error('Seul un brouillon existant peut être modifié.');
        }

        $current = $writer->toInput($project);
        $changes = collect($request->all())->only(array_keys($current));

        if ($changes->isEmpty()) {
            return Response::error('Aucun champ à modifier.');
        }

        $notes = $writer->save($project, [...$current, ...$changes->all()]);

        return Response::text($this->savedMessage($project->id, route('admin.projects.edit', $project), $notes)
            ."\nChamps modifiés : ".$changes->keys()->implode(', '));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du brouillon.')->required(),
            ...$this->draftFields($schema, requireMainFields: false),
        ];
    }
}
