<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostCommentRequest;
use App\Models\PostComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostCommentController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/post-comments/index', [
            'comments' => $this->paginateList(
                PostComment::query()->with('post:id,slug,title')->latest(),
                $request,
                ['author_name', 'author_email', 'body'],
                ['status'],
            ),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function update(PostCommentRequest $request, PostComment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Commentaire mis à jour.')]);

        return back();
    }

    public function destroy(PostComment $comment): RedirectResponse
    {
        $comment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Commentaire supprimé.')]);

        return back();
    }
}
