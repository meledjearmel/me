<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use Database\Factories\JobProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class JobProfile extends Model implements HasMedia
{
    /** @use HasFactory<JobProfileFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** Langues pour lesquelles un CV PDF peut être uploadé pour ce profil métier. */
    public const CV_LOCALES = ['fr', 'en'];

    /** @var array<int, string> */
    protected $translatable = ['label', 'description', 'hero_title', 'hero_words', 'cv_description'];

    /** @var list<string> */
    protected $fillable = [
        'key',
        'label',
        'description',
        // Titre du hero (ex. « Ingénieur logiciel qui ») et mots qui défilent à sa suite, séparés par des virgules.
        'hero_title',
        'hero_words',
        // Alimente uniquement le CV généré pour ce profil métier, jamais les pages publiques.
        'cv_description',
        'sort_order',
    ];

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_job_profile');
    }

    public function registerMediaCollections(): void
    {
        // CV fourni en PDF pour ce profil métier, par langue : s'il existe, il remplace le CV généré.
        foreach (self::CV_LOCALES as $locale) {
            $this->addMediaCollection(self::cvFileCollection($locale))
                ->singleFile()
                ->acceptsMimeTypes(['application/pdf']);
        }
    }

    /** Nom de la collection de médias du CV uploadé pour une langue. */
    public static function cvFileCollection(string $locale): string
    {
        return 'cv_file_'.$locale;
    }
}
