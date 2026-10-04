<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Concerns\SavesPosts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostImageRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use App\Models\PostTag;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @tags Blog
 */
class PostController extends Controller
{
    use PaginatesAdminLists, SavesPosts;

    /**
     * Liste des articles
     *
     * Du plus récent au plus ancien ; les brouillons (sans date) en premier.
     */
    #[QueryParameter('search', description: 'Recherche dans le titre et le slug.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `draft` ou `published`.', type: 'string')]
    #[QueryParameter('is_featured', description: 'Articles mis en avant : `1` ou `0`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return PostResource::collection(
            $this->paginateList(
                Post::query()->with(['tags', 'media'])->orderByRaw('published_at is not null')->latest('published_at')->latest(),
                $request,
                ['title->fr', 'title->en', 'slug'],
                ['status', 'is_featured'],
            )
        );
    }

    /**
     * Créer un article
     *
     * `body.fr` est en HTML (il est nettoyé par le serveur). Publié sans `published_at`, l'article paraît
     * tout de suite. `tags` est une liste de noms : les tags inconnus sont créés.
     * Requête `multipart/form-data` si `cover` (image) est envoyée.
     */
    public function store(PostRequest $request): PostResource
    {
        $post = DB::transaction(function () use ($request): Post {
            $post = Post::query()->create($this->postAttributes($request));
            $this->syncTags($post, $request);

            return $post;
        });

        $this->syncCover($post, $request);

        return $this->respond($post);
    }

    /**
     * Détail d'un article
     */
    public function show(Post $post): PostResource
    {
        return $this->respond($post);
    }

    /**
     * Modifier un article
     *
     * Avec une couverture, envoyer un `POST` en `multipart/form-data` avec le champ `_method=PUT`.
     */
    public function update(PostRequest $request, Post $post): PostResource
    {
        DB::transaction(function () use ($request, $post): void {
            $post->update($this->postAttributes($request, $post));
            $this->syncTags($post, $request);
        });

        $this->syncCover($post, $request);

        return $this->respond($post);
    }

    /**
     * Supprimer un article
     *
     * L'article part dans la corbeille.
     */
    public function destroy(Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }

    /**
     * Retirer la couverture
     */
    public function destroyCover(Post $post): PostResource
    {
        $post->clearMediaCollection('cover');

        return $this->respond($post);
    }

    /**
     * Importer une image du contenu
     *
     * Renvoie l'adresse publique de l'image, à insérer dans le HTML de l'article (`<img src="…">`).
     *
     * @response 201 array{url: string}
     */
    public function storeImage(PostImageRequest $request): JsonResponse
    {
        return response()->json(['url' => $this->storeContentImage($request)], 201);
    }

    /**
     * Tags existants
     *
     * Noms (en français) des tags déjà utilisés, pour l'autocomplétion.
     *
     * @response array{data: list<string>}
     */
    public function tags(): JsonResponse
    {
        return response()->json([
            'data' => PostTag::query()->orderBy('slug')->get()
                ->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))
                ->values(),
        ]);
    }

    private function respond(Post $post): PostResource
    {
        return new PostResource($post->refresh()->load(['tags', 'media']));
    }
}
