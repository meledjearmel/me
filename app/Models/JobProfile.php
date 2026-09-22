<?php

namespace App\Models;

use Database\Factories\JobProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class JobProfile extends Model
{
    /** @use HasFactory<JobProfileFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['label', 'description', 'cv_description'];

    /** @var array<int, string> */
    protected $fillable = [
        'key',
        'label',
        'description',
        // Alimente uniquement le CV généré pour ce profil métier, jamais les pages publiques.
        'cv_description',
        'sort_order',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_job_profile');
    }
}
