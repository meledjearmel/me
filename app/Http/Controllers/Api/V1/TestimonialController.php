<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\Api\V1\TestimonialResource;
use App\Models\Testimonial;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Témoignages
 */
class TestimonialController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des témoignages
     */
    #[QueryParameter('search', description: 'Recherche dans l’auteur, son rôle et le contenu.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `pending`, `approved` ou `rejected`.', type: 'string')]
    #[QueryParameter('is_featured', description: 'Témoignages à la une : `1` ou `0`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return TestimonialResource::collection(
            $this->paginateList(Testimonial::query()->with('project')->latest('submitted_at'), $request, ['author_name', 'author_email', 'author_role', 'content->fr', 'content->en'], ['status', 'is_featured'])
        );
    }

    /**
     * Détail d'un témoignage
     */
    public function show(Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($testimonial->load('project'));
    }

    /**
     * Modérer un témoignage
     *
     * Statut, projet associé et mise à la une (trois témoignages au maximum).
     */
    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $testimonial->update($request->validated());

        return new TestimonialResource($testimonial->load('project'));
    }

    /**
     * Supprimer un témoignage
     */
    public function destroy(Testimonial $testimonial): Response
    {
        $testimonial->delete();

        return response()->noContent();
    }
}
