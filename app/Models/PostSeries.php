<?php

namespace App\Models;

use Database\Factories\PostSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Série d'articles du blog (un tutoriel en plusieurs parties). Comme les tags, elle naît
 * depuis le formulaire d'un article ; son nom se corrige dans l'administration.
 */
class PostSeries extends Model
{
    /** @use HasFactory<PostSeriesFactory> */
    use HasFactory, HasTranslations;

    protected $table = 'post_series';

    /** @var array<int, string> */
    protected $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = ['name', 'slug'];

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
