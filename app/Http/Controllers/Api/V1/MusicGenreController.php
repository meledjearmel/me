<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MusicGenreRequest;
use App\Http\Resources\Api\V1\MusicGenreResource;
use App\Models\MusicGenre;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Registres de musique
 */
class MusicGenreController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des registres de musique
     */
    #[QueryParameter('search', description: 'Recherche dans la clé et le libellé.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return MusicGenreResource::collection(
            $this->paginateList(MusicGenre::query()->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en'])
        );
    }

    /**
     * Créer un registre de musique
     */
    public function store(MusicGenreRequest $request): MusicGenreResource
    {
        return new MusicGenreResource(MusicGenre::query()->create($request->validated()));
    }

    /**
     * Détail d'un registre de musique
     */
    public function show(MusicGenre $musicGenre): MusicGenreResource
    {
        return new MusicGenreResource($musicGenre);
    }

    /**
     * Modifier un registre de musique
     */
    public function update(MusicGenreRequest $request, MusicGenre $musicGenre): MusicGenreResource
    {
        $musicGenre->update($request->validated());

        return new MusicGenreResource($musicGenre);
    }

    /**
     * Supprimer un registre de musique
     */
    public function destroy(MusicGenre $musicGenre): Response
    {
        $musicGenre->delete();

        return response()->noContent();
    }
}
