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
            $this->paginateList(Testimonial::query()->with(['project', 'media'])->latest('submitted_at'), $request, ['author_name', 'author_email', 'author_role', 'content->fr', 'content->en'], ['status', 'is_featured'])
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
     * Statut, projet associé, mise à la une (trois témoignages au maximum), accroche,
     * transcription et vidéo.
     *
     * Avec une vidéo, envoyer un `POST` en `multipart/form-data` avec le champ `_method=PUT` :
     * PHP ne lit pas les fichiers d'une requête `PUT` directe. Formats acceptés : MP4, MOV,
     * WebM, MKV, 3GP ; 95 Mo au plus (Cloudflare refuse les corps de plus de 100 Mo). Une
     * nouvelle vidéo remplace l'ancienne. Elle est ensuite compressée en tâche de fond :
     * `video.poster_url`, `video.duration`, `video.width` et `video.height` restent nuls
     * jusqu'à la fin du traitement (quelques secondes à quelques minutes). Pour retirer la
     * vidéo, utiliser `DELETE /testimonials/{testimonial}/video`.
     */
    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $testimonial->update($request->safe()->except('video'));

        if ($request->hasFile('video')) {
            $testimonial->attachVideo($request->file('video'));
        }

        return new TestimonialResource($testimonial->refresh()->load('project'));
    }

    /**
     * Retirer la vidéo d'un témoignage
     *
     * La vidéo et son aperçu sont supprimés : le témoignage redevient un avis texte.
     */
    public function destroyVideo(Testimonial $testimonial): TestimonialResource
    {
        $testimonial->removeVideo();

        return new TestimonialResource($testimonial->refresh()->load('project'));
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
