<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewInvitationRequest;
use App\Http\Resources\Api\V1\ReviewInvitationResource;
use App\Models\ReviewInvitation;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Demandes d'avis
 */
class ReviewInvitationController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des demandes d'avis
     *
     * Du plus récent au plus ancien. Chaque demande est un lien personnel à usage unique
     * qui ouvre le formulaire d'avis prérempli.
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, l’email et le mémo.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `pending`, `used` ou `expired`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $query = ReviewInvitation::query()
            ->with(['project', 'experience', 'education'])
            ->when($status === 'pending', fn ($query) => $query->usable())
            ->when($status === 'used', fn ($query) => $query->whereNotNull('used_at'))
            ->when($status === 'expired', fn ($query) => $query->whereNull('used_at')->where('expires_at', '<=', now()))
            ->latest();

        return ReviewInvitationResource::collection($this->paginateList($query, $request, ['name', 'email', 'note'], []));
    }

    /**
     * Créer une demande d'avis
     *
     * Génère le lien personnel (`url`) à envoyer à la personne.
     */
    public function store(ReviewInvitationRequest $request): ReviewInvitationResource
    {
        return new ReviewInvitationResource(ReviewInvitation::query()->create($request->invitationData()));
    }

    /**
     * Supprimer une demande d'avis
     *
     * Le lien cesse de fonctionner ; un avis déjà reçu reste en place.
     */
    public function destroy(ReviewInvitation $reviewInvitation): Response
    {
        $reviewInvitation->delete();

        return response()->noContent();
    }
}
