<?php

namespace App\Concerns;

use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'un projet (champs, relations, médias) partagé par l'administration et l'API.
 */
trait SavesProjects
{
    /**
     * Les champs du projet, sans les relations ni les médias.
     *
     * @return array<string, mixed>
     */
    private function projectAttributes(ProjectRequest $request): array
    {
        return $request->safe()->except([
            'cover', 'gallery', 'domains', 'job_profiles', 'technologies', 'related_projects',
        ]);
    }

    private function syncRelations(Project $project, ProjectRequest $request): void
    {
        $project->domains()->sync($request->validated('domains', []));
        $project->jobProfiles()->sync($request->validated('job_profiles', []));
        $project->technologies()->sync($request->validated('technologies', []));
        $this->syncRelatedProjects($project, $request->validated('related_projects', []));
    }

    /**
     * Maintient la relation "projets liés" symétrique : chaque paire est stockée
     * dans les deux sens pour que le groupement apparaisse depuis chaque projet.
     *
     * @param  array<int, int>  $relatedIds
     */
    private function syncRelatedProjects(Project $project, array $relatedIds): void
    {
        DB::table('project_related')
            ->where('project_id', $project->id)
            ->orWhere('related_project_id', $project->id)
            ->delete();

        foreach ($relatedIds as $relatedId) {
            DB::table('project_related')->insert(['project_id' => $project->id, 'related_project_id' => $relatedId]);
            DB::table('project_related')->insert(['project_id' => $relatedId, 'related_project_id' => $project->id]);
        }
    }

    /** @return array<int, int> */
    private function relatedProjectIds(Project $project): array
    {
        return DB::table('project_related')
            ->where('project_id', $project->id)
            ->orWhere('related_project_id', $project->id)
            ->get()
            ->flatMap(fn ($row) => [$row->project_id, $row->related_project_id])
            ->reject(fn (int $id): bool => $id === $project->id)
            ->unique()
            ->values()
            ->all();
    }

    private function syncMedia(Project $project, ProjectRequest $request): void
    {
        if ($request->hasFile('cover')) {
            $project->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($request->file('gallery', []) as $file) {
            $project->addMedia($file)->toMediaCollection('gallery');
        }
    }
}
