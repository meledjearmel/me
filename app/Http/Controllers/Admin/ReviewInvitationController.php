<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewInvitationRequest;
use App\Http\Resources\Api\V1\ReviewInvitationResource;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\ReviewInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Liens pour demander un avis : je crée le lien, je le copie et je l'envoie ; il ouvre le
 * formulaire d'avis prérempli. Personnel, il ne sert qu'une fois ; collectif, il sert à
 * toute une équipe.
 */
class ReviewInvitationController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = ReviewInvitation::query()
            ->with([...ReviewInvitation::SUBJECT_RELATIONS, 'testimonials:id,review_invitation_id'])
            ->when($status === 'pending', fn ($query) => $query->usable())
            ->when($status === 'used', fn ($query) => $query->whereNotNull('used_at'))
            ->when($status === 'expired', fn ($query) => $query->whereNull('used_at')->where('expires_at', '<=', now()))
            ->latest();

        $invitations = $this->paginateList($query, $request, ['name', 'email', 'note'], []);

        return Inertia::render('admin/review-invitations/index', [
            'invitations' => $invitations->setCollection(
                $invitations->getCollection()->map(fn (ReviewInvitation $invitation): array => (new ReviewInvitationResource($invitation))->resolve()),
            ),
            'filters' => [
                ...$this->listFilters($request, []),
                'status' => is_string($status) ? $status : '',
            ],
            'projects' => Project::query()->orderBy('sort_order')->get(['id', 'title']),
            'experiences' => Experience::query()->orderByDesc('start_date')->get(['id', 'company', 'role']),
            'educations' => Education::query()->orderByDesc('start_date')->get(['id', 'institution', 'degree']),
        ]);
    }

    public function store(ReviewInvitationRequest $request): RedirectResponse
    {
        $invitation = ReviewInvitation::query()->create($request->invitationData());
        $invitation->syncSubjects($request->subjects());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lien créé, il ne reste qu’à le copier.')]);

        return to_route('admin.review-invitations.index');
    }

    /** Révoque le lien : il ne fonctionne plus. Les avis déjà reçus restent en place. */
    public function destroy(ReviewInvitation $reviewInvitation): RedirectResponse
    {
        $reviewInvitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lien supprimé.')]);

        return to_route('admin.review-invitations.index');
    }
}
