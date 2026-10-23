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
     * Du plus récent au plus ancien. Chaque demande est un lien qui ouvre le formulaire
     * d'avis prérempli : personnel (une seule utilisation) ou collectif (plusieurs personnes).
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, l’email et le mémo.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `pending`, `used` ou `expired`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $query = ReviewInvitation::query()
            ->with([...ReviewInvitation::SUBJECT_RELATIONS, 'testimonials:id,review_invitation_id'])
            ->when($status === 'pending', fn ($query) => $query->usable())
            ->when($status === 'used', fn ($query) => $query->whereNotNull('used_at'))
            ->when($status === 'expired', fn ($query) => $query->whereNull('used_at')->where('expires_at', '<=', now()))
            ->latest();

        return ReviewInvitationResource::collection($this->paginateList($query, $request, ['name', 'email', 'note'], []));
    }

    /**
     * Créer une demande d'avis
     *
     * Génère le lien (`url`) à envoyer. Rattachements : `project_ids`, `experience_ids` et
     * `education_ids` (plusieurs possibles ; les anciens champs uniques `project_id`… restent
     * acceptés). Avec `collective: true`, le lien sert à plusieurs personnes (`max_uses` avis au
     * plus, sans limite s'il est vide) et ne préremplit ni nom ni e-mail ; une même adresse
     * e-mail ne peut y laisser qu'un avis.
     */
    public function store(ReviewInvitationRequest $request): ReviewInvitationResource
    {
        $invitation = ReviewInvitation::query()->create($request->invitationData());
        $invitation->syncSubjects($request->subjects());

        return new ReviewInvitationResource($invitation->load([...ReviewInvitation::SUBJECT_RELATIONS, 'testimonials']));
    }

    /**
     * Supprimer une demande d'avis
     *
     * Le lien cesse de fonctionner ; les avis déjà reçus restent en place.
     */
    public function destroy(ReviewInvitation $reviewInvitation): Response
    {
        $reviewInvitation->delete();

        return response()->noContent();
    }
}
