<?php

namespace App\Models;

use Database\Factories\PostTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/** Étiquette d'un article du blog. */
class PostTag extends Model
{
    /** @use HasFactory<PostTagFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    protected $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = ['name', 'slug'];

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
