<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use Database\Factories\JobProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class JobProfile extends Model
{
    /** @use HasFactory<JobProfileFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, SoftDeletes;

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
}
