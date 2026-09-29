<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CongratulationResource;
use App\Models\Congratulation;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Félicitations
 */
class CongratulationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Historique des félicitations
     *
     * Un élément par envoi du navigateur (les clics d'un visiteur sont regroupés), du plus récent au plus ancien.
     */
    #[QueryParameter('search', description: 'Recherche dans le motif.', type: 'string')]
    #[QueryParameter('source', description: 'Origine : `about` (page À propos) ou `surprise`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return CongratulationResource::collection(
            $this->paginateList(Congratulation::query()->latest()->latest('id'), $request, ['reason'], ['source'])
        );
    }

    /**
     * Détail d'une félicitation
     *
     * Cible de la notification push « Nouvelles félicitations ».
     */
    public function show(Congratulation $congratulation): CongratulationResource
    {
        return new CongratulationResource($congratulation);
    }
}
