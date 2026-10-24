<?php

namespace App\Models;

use App\Concerns\HasSubjects;
use App\Enums\TestimonialStatus;
use App\Jobs\ProcessTestimonialVideo;
use App\Support\WebVtt;
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
    use HasFactory, HasSubjects, HasTranslations, InteractsWithMedia, SoftDeletes;

    public const string VIDEO_COLLECTION = 'video';

    public const string POSTER_COLLECTION = 'video_poster';

    /** @var array<int, string> */
    protected $translatable = ['content', 'highlight', 'video_transcript', 'video_captions'];

    /** Les sous-titres sont servis par leur propre route, pas dans les props. */
    protected $hidden = ['video_captions'];

    /** @var list<string> */
    protected $fillable = [
        'author_name',
        'author_email',
        'author_role',
        'content',
        'highlight',
        'video_transcript',
        'review_invitation_id',
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
        $this->update(['video_captions' => null]);
    }

    /**
     * Remplace les sous-titres des langues reçues (fichier .vtt ou .srt, converti en
     * WebVTT) et retire ceux des langues demandées.
     *
     * @param  array<string, UploadedFile>  $files
     * @param  list<string>  $languagesToRemove
     */
    public function syncCaptions(array $files, array $languagesToRemove = []): void
    {
        foreach ($languagesToRemove as $language) {
            $this->forgetTranslation('video_captions', $language);
        }

        foreach ($files as $language => $file) {
            $captions = WebVtt::fromSubtitles((string) file_get_contents($file->getRealPath()));

            if ($captions !== null) {
                $this->setTranslation('video_captions', $language, $captions);
            }
        }

        $this->save();
    }

    /**
     * Les pistes de sous-titres disponibles, pour les balises <track> du lecteur.
     *
     * @return list<array{language: string, url: string}>
     */
    public function captionTracks(): array
    {
        $tracks = [];

        foreach ($this->getTranslations('video_captions') as $language => $captions) {
            if (filled($captions)) {
                $tracks[] = [
                    'language' => $language,
                    'url' => route('testimonials.captions', ['testimonial' => $this, 'language' => $language]),
                ];
            }
        }

        return $tracks;
    }

    /**
     * La vidéo telle que l'affiche le site. La durée et les dimensions ne sont
     * connues qu'une fois la vidéo traitée par ffmpeg.
     *
     * @return array{url: string, poster_url: string|null, duration: int|null, width: int|null, height: int|null, uploaded_at: string|null, captions: list<array{language: string, url: string}>}|null
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
            'uploaded_at' => $video->created_at?->toIso8601String(),
            'captions' => $this->captionTracks(),
        ];
    }

    /**
     * Le lien d'invitation par lequel l'avis est arrivé.
     *
     * @return BelongsTo<ReviewInvitation, $this>
     */
    public function reviewInvitation(): BelongsTo
    {
        return $this->belongsTo(ReviewInvitation::class);
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
        $approved = static::query()->with(['media', ...self::SUBJECT_RELATIONS])->where('status', TestimonialStatus::Approved);

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
