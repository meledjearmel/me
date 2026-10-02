<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CvDownloadResource;
use App\Models\CvDownload;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Téléchargements du CV
 */
class CvDownloadController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des téléchargements du CV
     *
     * Du plus récent au plus ancien. Un téléchargement est enregistré quand un
     * visiteur (hors robots) télécharge le CV depuis la page contact du site ;
     * un second téléchargement le même jour par le même visiteur n'est pas compté.
     */
    #[QueryParameter('search', description: 'Recherche dans l’email, la ville, le pays, le site d’origine et la campagne.', type: 'string')]
    #[QueryParameter('country_code', description: 'Code ISO du pays (`CI`, `FR`…).', type: 'string')]
    #[QueryParameter('locale', description: 'Langue du CV : `fr` ou `en`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return CvDownloadResource::collection(
            $this->paginateList(
                CvDownload::query()->with('jobProfile')->latest(),
                $request,
                ['email', 'city', 'country', 'referrer_host', 'utm_source', 'utm_campaign'],
                ['country_code', 'locale'],
            )
        );
    }

    /**
     * Détail d'un téléchargement du CV
     */
    public function show(CvDownload $cvDownload): CvDownloadResource
    {
        return new CvDownloadResource($cvDownload->load('jobProfile'));
    }

    /**
     * Supprimer un téléchargement du CV
     */
    public function destroy(CvDownload $cvDownload): Response
    {
        $cvDownload->delete();

        return response()->noContent();
    }
}
