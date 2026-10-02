<?php

namespace App\Models;

use App\Enums\TestimonialStatus;
use App\Jobs\ProcessTestimonialVideo;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model implements HasMedia
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    public const string VIDEO_COLLECTION = 'video';

    public const string POSTER_COLLECTION = 'video_poster';

    /** @var array<int, string> */
    protected $translatable = ['content', 'highlight', 'video_transcript'];

    /** @var list<string> */
    protected $fillable = [
        'author_name',
        'author_email',
        'author_role',
        'content',
        'highlight',
        'video_transcript',
        'project_id',
        'status',
        'is_featured',
        'submitted_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => TestimonialStatus::class,
        'is_featured' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::VIDEO_COLLECTION)->singleFile();
        $this->addMediaCollection(self::POSTER_COLLECTION)->singleFile();
    }

    /**
     * Remplace la vidéo de l'avis (et retire l'ancien aperçu), puis la prépare
     * pour le web en tâche de fond.
     */
    public function attachVideo(UploadedFile $file): void
    {
        $this->clearMediaCollection(self::POSTER_COLLECTION);

        $video = $this->addMedia($file)->toMediaCollection(self::VIDEO_COLLECTION);

        ProcessTestimonialVideo::dispatch($video);
    }

    public function removeVideo(): void
    {
        $this->clearMediaCollection(self::VIDEO_COLLECTION);
        $this->clearMediaCollection(self::POSTER_COLLECTION);
    }

    /**
     * La vidéo telle que l'affiche le site. La durée et les dimensions ne sont
     * connues qu'une fois la vidéo traitée par ffmpeg.
     *
     * @return array{url: string, poster_url: string|null, duration: int|null, width: int|null, height: int|null}|null
     */
    public function videoData(): ?array
    {
        $video = $this->getFirstMedia(self::VIDEO_COLLECTION);

        if ($video === null) {
            return null;
        }

        return [
            'url' => $video->getUrl(),
            'poster_url' => $this->getFirstMediaUrl(self::POSTER_COLLECTION) ?: null,
            'duration' => $video->getCustomProperty('duration'),
            'width' => $video->getCustomProperty('width'),
            'height' => $video->getCustomProperty('height'),
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Nombre maximum d'avis « à la une » (et de cartes du paquet de l'accueil). */
    public const int FEATURED_LIMIT = 3;

    /**
     * Les avis du paquet de l'accueil : ceux que j'ai choisis (approuvés et « à la
     * une », trois au plus, même si un seul est choisi). Sans aucun choix, trois
     * avis approuvés tirés au hasard.
     *
     * @return Collection<int, static>
     */
    public static function forHomepage(): Collection
    {
        $approved = static::query()->with('media')->where('status', TestimonialStatus::Approved);

        $chosen = (clone $approved)
            ->where('is_featured', true)
            ->latest('submitted_at')
            ->limit(self::FEATURED_LIMIT)
            ->get();

        return $chosen->isNotEmpty()
            ? $chosen
            : $approved->inRandomOrder()->limit(self::FEATURED_LIMIT)->get();
    }
}
