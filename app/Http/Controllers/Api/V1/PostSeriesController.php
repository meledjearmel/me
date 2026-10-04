<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostSeriesRequest;
use App\Http\Resources\Api\V1\PostSeriesResource;
use App\Models\PostSeries;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Les séries naissent avec les articles (champs `series` et `series_position` d'un
 * article) ; on corrige ici leur nom français et anglais, ou on les supprime.
 *
 * @tags Séries du blog
 */
class PostSeriesController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des séries d'articles
     */
    #[QueryParameter('search', description: 'Recherche dans le slug et le nom.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return PostSeriesResource::collection(
            $this->paginateList(PostSeries::query()->withCount('posts')->orderBy('slug'), $request, ['slug', 'name->fr', 'name->en'])
        );
    }

    /**
     * Détail d'une série d'articles
     */
    public function show(PostSeries $postSeries): PostSeriesResource
    {
        return new PostSeriesResource($postSeries->loadCount('posts'));
    }

    /**
     * Renommer une série d'articles
     */
    public function update(PostSeriesRequest $request, PostSeries $postSeries): PostSeriesResource
    {
        $postSeries->update($request->validated());

        return new PostSeriesResource($postSeries->loadCount('posts'));
    }

    /**
     * Supprimer une série d'articles
     *
     * Suppression définitive : ses articles restent, hors série.
     */
    public function destroy(PostSeries $postSeries): Response
    {
        $postSeries->posts()->update(['series_position' => null]);
        $postSeries->delete();

        return response()->noContent();
    }
}
