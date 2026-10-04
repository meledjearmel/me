<?php

namespace App\Models;

use App\Enums\PostReactionType;
use Database\Factories\PostReactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Réaction anonyme d'un lecteur à un article. Le lecteur n'est connu que par l'empreinte
 * de son cookie : une réaction de chaque type par lecteur et par article.
 */
class PostReaction extends Model
{
    /** @use HasFactory<PostReactionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'type',
        'reader_hash',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => PostReactionType::class,
    ];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
