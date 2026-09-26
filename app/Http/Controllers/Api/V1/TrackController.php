<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrackRequest;
use App\Http\Resources\Api\V1\TrackResource;
use App\Models\Track;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Pistes de musique
 */
class TrackController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des pistes
     */
    #[QueryParameter('search', description: 'Recherche dans le titre et l’artiste.', type: 'string')]
    #[QueryParameter('music_genre_id', description: 'Identifiant du registre.', type: 'integer')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return TrackResource::collection(
            $this->paginateList(Track::query()->orderBy('music_genre_id')->orderBy('sort_order'), $request, ['title', 'artist'], ['music_genre_id'])
        );
    }

    /**
     * Créer une piste (envoi multipart avec le fichier `audio`)
     */
    public function store(TrackRequest $request): TrackResource
    {
        $track = Track::query()->create($request->safe()->except('audio'));
        $track->addMediaFromRequest('audio')->toMediaCollection('audio');

        return new TrackResource($track);
    }

    /**
     * Détail d'une piste
     */
    public function show(Track $track): TrackResource
    {
        return new TrackResource($track);
    }

    /**
     * Modifier une piste (le fichier `audio` est facultatif)
     */
    public function update(TrackRequest $request, Track $track): TrackResource
    {
        $track->update($request->safe()->except('audio'));

        if ($request->hasFile('audio')) {
            $track->addMediaFromRequest('audio')->toMediaCollection('audio');
        }

        return new TrackResource($track);
    }

    /**
     * Supprimer une piste
     */
    public function destroy(Track $track): Response
    {
        $track->delete();

        return response()->noContent();
    }
}
