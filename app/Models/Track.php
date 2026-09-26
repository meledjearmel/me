<?php

namespace App\Models;

use Database\Factories\TrackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Une piste du lecteur : un fichier audio rattaché à un registre. */
class Track extends Model implements HasMedia
{
    /** @use HasFactory<TrackFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'music_genre_id',
        'title',
        'artist',
        'sort_order',
    ];

    /** @return BelongsTo<MusicGenre, $this> */
    public function genre(): BelongsTo
    {
        return $this->belongsTo(MusicGenre::class, 'music_genre_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('audio')
            ->singleFile()
            ->acceptsMimeTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a', 'audio/aac']);
    }

    /** URL du fichier audio, ou null tant qu'il n'a pas été envoyé. */
    public function audioUrl(): ?string
    {
        return $this->getFirstMediaUrl('audio') ?: null;
    }
}
