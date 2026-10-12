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
use Laravel\Mcp\Server\Tool;

#[Description('Crée un brouillon de projet en français, ou remplace entièrement un brouillon existant (avec id) : un champ absent est vidé. Pour changer seulement quelques champs, utiliser update-project-draft. Le brouillon n\'est visible que dans l\'administration : le développeur le relit, le traduit en anglais et le publie lui-même. Un projet publié ou archivé ne peut pas être modifié. N\'invente rien : un fait absent de la source reste vide.')]
class SaveProjectDraftTool extends Tool
{
    use DescribesProjectDraft;

    public function handle(Request $request, ProjectDraftWriter $writer): Response
    {
        $project = $request->get('id') !== null
            ? Project::query()->find($request->get('id'))
            : new Project(['status' => ProjectStatus::Draft]);

        if ($project?->status !== ProjectStatus::Draft) {
            return Response::error('Seul un brouillon existant peut être modifié.');
        }

        $notes = $writer->save($project, collect($request->all())->except('id')->all());

        return Response::text($this->savedMessage($project->id, route('admin.projects.edit', $project), $notes));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant d\'un brouillon existant à remplacer ; absent pour en créer un.'),
            ...$this->draftFields($schema, requireMainFields: true),
        ];
    }
}
