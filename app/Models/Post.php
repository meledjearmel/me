<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Services\PostContent;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Article du blog. Le contenu est rédigé dans l'éditeur Tiptap de l'admin et stocké en HTML,
 * nettoyé à l'enregistrement (voir PostContent).
 */
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** Vitesse de lecture moyenne, en mots par minute. */
    private const int WORDS_PER_MINUTE = 220;

    /** @var array<int, string> */
    protected $translatable = ['title', 'excerpt', 'body'];

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'is_featured',
        'status',
        'published_at',
        'post_series_id',
        'series_position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_featured' => 'boolean',
        'status' => PublicationStatus::class,
        'published_at' => 'datetime',
        'newsletter_sent_at' => 'datetime',
        'reading_minutes' => 'integer',
        'series_position' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            $content = app(PostContent::class);

            if ($post->isDirty('body')) {
                $post->setTranslations('body', array_map(
                    fn (?string $body): string => $content->sanitize($body),
                    $post->getTranslations('body'),
                ));
            }

            $post->reading_minutes = $post->computeReadingMinutes($content);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('images');
    }

    /** @return BelongsTo<PostSeries, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(PostSeries::class, 'post_series_id');
    }

    /** @return BelongsToMany<PostTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class);
    }

    /**
     * Articles visibles publiquement : publiés et dont la date de publication est passée.
     *
     * @param  Builder<Post>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /** Durée de validité d'un lien d'aperçu, en heures. */
    public const int PREVIEW_HOURS = 72;

    /** Lien signé pour relire l'article tel qu'il apparaîtra sur le site, même non publié. */
    public function previewUrl(string $locale = 'fr'): string
    {
        return URL::temporarySignedRoute(
            'blog.preview',
            now()->addHours(self::PREVIEW_HOURS),
            ['locale' => $locale, 'post' => $this->slug],
        );
    }

    /** Temps de lecture estimé sur la version la plus longue du contenu. */
    private function computeReadingMinutes(PostContent $content): int
    {
        $words = collect($this->getTranslations('body'))
            ->map(fn (?string $body): int => str_word_count($content->text($body)))
            ->max() ?? 0;

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }
}
