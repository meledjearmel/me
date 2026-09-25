<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['title', 'context', 'realization', 'result'];

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'context',
        'realization',
        'result',
        'accent_color',
        'repo_url',
        'demo_url',
        'is_featured',
        'is_open_source',
        'status',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_open_source' => 'boolean',
        'status' => ProjectStatus::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    /** @return BelongsToMany<Domain, $this> */
    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'project_domain');
    }

    /** @return BelongsToMany<JobProfile, $this> */
    public function jobProfiles(): BelongsToMany
    {
        return $this->belongsToMany(JobProfile::class, 'project_job_profile');
    }

    /** @return BelongsToMany<Technology, $this> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'project_technology');
    }

    /**
     * Groupes de projets liés (App Station+Registra, GESMAR+GesmarVerif...).
     * La relation étant conceptuellement symétrique, les deux sens doivent être
     * attachés explicitement (attach() dans les deux sens) lors de la liaison.
     */
    /** @return BelongsToMany<Project, $this> */
    public function relatedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_related', 'project_id', 'related_project_id');
    }

    /** @return HasMany<Testimonial, $this> */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /** @return HasMany<ProfessionalReference, $this> */
    public function professionalReferences(): HasMany
    {
        return $this->hasMany(ProfessionalReference::class);
    }
}
