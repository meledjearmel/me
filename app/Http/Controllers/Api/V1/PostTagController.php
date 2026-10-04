<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostTagRequest;
use App\Http\Resources\Api\V1\PostTagResource;
use App\Models\PostTag;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Les tags naissent avec les articles (champ `tags` d'un article) ; on corrige ici
 * leur nom français et anglais, ou on les supprime. Le slug ne change pas.
 *
 * @tags Tags du blog
 */
class PostTagController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des tags du blog
     */
    #[QueryParameter('search', description: 'Recherche dans le slug et le nom.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return PostTagResource::collection(
            $this->paginateList(PostTag::query()->withCount('posts')->orderBy('slug'), $request, ['slug', 'name->fr', 'name->en'])
        );
    }

    /**
     * Détail d'un tag du blog
     */
    public function show(PostTag $postTag): PostTagResource
    {
        return new PostTagResource($postTag->loadCount('posts'));
    }

    /**
     * Renommer un tag du blog
     */
    public function update(PostTagRequest $request, PostTag $postTag): PostTagResource
    {
        $postTag->update($request->validated());

        return new PostTagResource($postTag->loadCount('posts'));
    }

    /**
     * Supprimer un tag du blog
     *
     * Suppression définitive : le tag est retiré des articles qui le portent.
     */
    public function destroy(PostTag $postTag): Response
    {
        $postTag->posts()->detach();
        $postTag->delete();

        return response()->noContent();
    }
}
