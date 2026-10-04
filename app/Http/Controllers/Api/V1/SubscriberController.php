<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriberResource;
use App\Models\Subscriber;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Newsletter
 */
class SubscriberController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des abonnés à la newsletter
     *
     * Du plus récent au plus ancien. Les abonnés actifs reçoivent chaque nouvel article
     * du blog ; une inscription n'est active qu'après confirmation par email.
     * `summary` donne les totaux par statut, quels que soient les filtres.
     */
    #[QueryParameter('search', description: 'Recherche dans l’email.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `active`, `pending` ou `unsubscribed`.', type: 'string')]
    #[QueryParameter('locale', description: 'Langue des emails : `fr` ou `en`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $query = Subscriber::query()
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status === 'pending', fn ($query) => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($query) => $query->whereNotNull('unsubscribed_at'))
            ->latest();

        return SubscriberResource::collection($this->paginateList($query, $request, ['email'], ['locale']))
            ->additional(['summary' => [
                'active' => Subscriber::query()->active()->count(),
                'pending' => Subscriber::query()->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                'unsubscribed' => Subscriber::query()->whereNotNull('unsubscribed_at')->count(),
            ]]);
    }

    /**
     * Supprimer un abonné
     *
     * Suppression définitive : l'adresse pourra se réinscrire depuis le blog.
     */
    public function destroy(Subscriber $subscriber): Response
    {
        $subscriber->delete();

        return response()->noContent();
    }
}
