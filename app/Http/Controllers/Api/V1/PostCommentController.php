<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostCommentRequest;
use App\Http\Resources\Api\V1\PostCommentResource;
use App\Models\PostComment;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Commentaires du blog
 */
class PostCommentController extends Controller
{
    use PaginatesAdminLists;

    /**
     * Liste des commentaires
     *
     * Du plus récent au plus ancien, tous articles confondus.
     */
    #[QueryParameter('search', description: 'Recherche dans l’auteur, son email et le commentaire.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `pending`, `approved` ou `rejected`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return PostCommentResource::collection(
            $this->paginateList(PostComment::query()->with('post:id,slug,title')->latest(), $request, ['author_name', 'author_email', 'body'], ['status'])
        );
    }

    /**
     * Détail d'un commentaire
     */
    public function show(PostComment $comment): PostCommentResource
    {
        return new PostCommentResource($comment->load('post:id,slug,title'));
    }

    /**
     * Modérer un commentaire
     *
     * `approved` l'affiche sous l'article, `rejected` le masque.
     */
    public function update(PostCommentRequest $request, PostComment $comment): PostCommentResource
    {
        $comment->update($request->validated());

        return new PostCommentResource($comment->load('post:id,slug,title'));
    }

    /**
     * Supprimer un commentaire
     *
     * Le commentaire passe dans la corbeille (type `post-comments`), d'où il peut être restauré.
     */
    public function destroy(PostComment $comment): Response
    {
        $comment->delete();

        return response()->noContent();
    }
}
