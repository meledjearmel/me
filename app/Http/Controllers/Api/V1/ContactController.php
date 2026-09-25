<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Contacts
 */
class ContactController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des messages
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, l’e-mail et le sujet.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `new`, `read` ou `replied`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return ContactResource::collection(
            $this->paginateList(Contact::query()->latest(), $request, ['name', 'email', 'subject'], ['status'])
        );
    }

    /**
     * Détail d'un message
     *
     * Un message « nouveau » passe à « lu » à sa première consultation.
     */
    public function show(Contact $contact): ContactResource
    {
        if ($contact->status === ContactStatus::New) {
            $contact->update(['status' => ContactStatus::Read]);
        }

        return new ContactResource($contact);
    }

    /**
     * Changer le statut d'un message
     */
    public function update(ContactRequest $request, Contact $contact): ContactResource
    {
        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    /**
     * Supprimer un message
     */
    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
