<?php

namespace App\Models;

use Database\Factories\MusicGenreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/** Un registre de musique du lecteur (lo-fi, jazz…). */
class MusicGenre extends Model
{
    /** @use HasFactory<MusicGenreFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['label'];

    /** @var list<string> */
    protected $fillable = [
        'key',
        'label',
        'sort_order',
    ];

    /** @return HasMany<Track, $this> */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }
}
