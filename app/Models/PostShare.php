<?php

namespace App\Models;

use App\Enums\PostShareNetwork;
use Database\Factories\PostShareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partage anonyme d'un article depuis le site : un par session, article et réseau
 * sur 30 minutes, robots exclus.
 */
class PostShare extends Model
{
    /** @use HasFactory<PostShareFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'network',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'network' => PostShareNetwork::class,
    ];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
