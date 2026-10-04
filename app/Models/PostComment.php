<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Database\Factories\PostCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commentaire d'un lecteur sous un article. Il reste en attente jusqu'à sa validation
 * dans l'admin ; l'email de l'auteur n'est jamais affiché. Supprimé, il passe par la corbeille.
 */
class PostComment extends Model
{
    /** @use HasFactory<PostCommentFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'author_name',
        'author_email',
        'body',
        'locale',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => CommentStatus::class,
    ];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Commentaires visibles sur le site.
     *
     * @param  Builder<PostComment>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', CommentStatus::Approved);
    }
}
