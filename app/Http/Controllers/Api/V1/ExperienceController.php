<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Http\Resources\Api\V1\ExperienceResource;
use App\Models\Experience;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @tags Expériences
 */
class ExperienceController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des expériences
     */
    #[QueryParameter('search', description: 'Recherche dans la société, le rôle et le lieu.', type: 'string')]
    #[QueryParameter('status', description: 'Statut de publication : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ExperienceResource::collection(
            $this->paginateList(Experience::query()->orderBy('sort_order'), $request, ['company', 'role->fr', 'role->en', 'location'], ['status'])
        );
    }

    /**
     * Créer une expérience
     *
     * `highlights` est la liste des points marquants (`text.fr`, `text.en`, `sort_order`).
     */
    public function store(ExperienceRequest $request): ExperienceResource
    {
        $experience = DB::transaction(function () use ($request): Experience {
            $experience = Experience::query()->create($request->safe()->except('highlights'));

            $this->syncHighlights($experience, $request->validated('highlights', []));

            return $experience;
        });

        return new ExperienceResource($experience->load('highlights'));
    }

    /**
     * Détail d'une expérience
     */
    public function show(Experience $experience): ExperienceResource
    {
        return new ExperienceResource($experience->load('highlights'));
    }

    /**
     * Modifier une expérience
     *
     * Les points marquants absents de `highlights` sont supprimés ; ceux qui portent un `id` sont mis à jour.
     */
    public function update(ExperienceRequest $request, Experience $experience): ExperienceResource
    {
        DB::transaction(function () use ($request, $experience): void {
            $experience->update($request->safe()->except('highlights'));

            $this->syncHighlights($experience, $request->validated('highlights', []));
        });

        return new ExperienceResource($experience->load('highlights'));
    }

    /**
     * Supprimer une expérience
     */
    public function destroy(Experience $experience): Response
    {
        $experience->delete();

        return response()->noContent();
    }

    /**
     * @param  array<int, array{id?: int|null, text: array<string, string>, sort_order: int}>  $highlights
     */
    private function syncHighlights(Experience $experience, array $highlights): void
    {
        $keptIds = [];

        foreach ($highlights as $highlight) {
            $model = $experience->highlights()->updateOrCreate(
                ['id' => $highlight['id'] ?? null],
                [
                    'text' => $highlight['text'],
                    'sort_order' => $highlight['sort_order'],
                ],
            );

            $keptIds[] = $model->id;
        }

        $experience->highlights()->whereNotIn('id', $keptIds)->delete();
    }
}
