<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @tags Compétences
 */
class SkillController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des compétences
     */
    #[QueryParameter('search', description: 'Recherche dans le nom.', type: 'string')]
    #[QueryParameter('domain_id', description: 'Filtre par domaine.', type: 'integer')]
    #[QueryParameter('status', description: 'Statut de publication : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return SkillResource::collection(
            $this->paginateList(Skill::query()->with('domain')->orderBy('sort_order'), $request, ['name->fr', 'name->en'], ['domain_id', 'status'])
        );
    }

    /**
     * Créer une compétence
     *
     * `technologies` est la liste ordonnée des identifiants de technologies liées.
     */
    public function store(SkillRequest $request): SkillResource
    {
        $skill = DB::transaction(function () use ($request): Skill {
            $skill = Skill::query()->create($request->safe()->except('technologies'));

            $this->syncTechnologies($skill, $request->validated('technologies', []));

            return $skill;
        });

        return new SkillResource($skill->load(['domain', 'technologies']));
    }

    /**
     * Détail d'une compétence
     */
    public function show(Skill $skill): SkillResource
    {
        return new SkillResource($skill->load(['domain', 'technologies']));
    }

    /**
     * Modifier une compétence
     */
    public function update(SkillRequest $request, Skill $skill): SkillResource
    {
        DB::transaction(function () use ($request, $skill): void {
            $skill->update($request->safe()->except('technologies'));

            $this->syncTechnologies($skill, $request->validated('technologies', []));
        });

        return new SkillResource($skill->load(['domain', 'technologies']));
    }

    /**
     * Supprimer une compétence
     */
    public function destroy(Skill $skill): Response
    {
        $skill->delete();

        return response()->noContent();
    }

    /**
     * Relie les technologies en gardant l'ordre reçu.
     *
     * @param  array<int, int|string>  $technologyIds
     */
    private function syncTechnologies(Skill $skill, array $technologyIds): void
    {
        $skill->technologies()->sync(
            collect($technologyIds)
                ->values()
                ->mapWithKeys(fn (int|string $id, int $index): array => [(int) $id => ['sort_order' => $index]])
                ->all(),
        );
    }
}
