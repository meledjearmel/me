<?php

namespace App\Models;

use App\Enums\SyndicationPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La republication d'un article sur une plateforme (dev.to, Hashnode) : où il en est, son
 * adresse là-bas, et l'erreur du dernier essai s'il a échoué. Une par article et plateforme :
 * republier met à jour l'article existant au lieu d'en créer un autre.
 */
class PostSyndication extends Model
{
    public const PENDING = 'pending';

    public const PUBLISHED = 'published';

    public const FAILED = 'failed';

    /** @var list<string> */
    protected $fillable = [
        'platform',
        'status',
        'external_id',
        'external_url',
        'locale',
        'error',
        'published_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'platform' => SyndicationPlatform::class,
        'published_at' => 'datetime',
    ];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
