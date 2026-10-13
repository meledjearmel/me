<?php

namespace App\Models;

use App\Enums\JobApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Une page de candidature : privée (adresse difficile à deviner, jamais indexée), faite pour
 * une entreprise et un poste, avec un mot d'accompagnement, une sélection de projets et le
 * CV du profil visé. On voit quand elle est consultée et quand le CV est téléchargé.
 */
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'company',
        'contact_name',
        'position',
        'job_profile_id',
        'locale',
        'intro',
        'job_posting_url',
        'job_posting_text',
        'notes',
        'status',
        'expires_on',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => JobApplicationStatus::class,
        'cv_tailoring' => 'array',
        'cv_tailored_at' => 'datetime',
        'expires_on' => 'date:Y-m-d',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'views_count' => 'integer',
        'cv_downloads_count' => 'integer',
    ];

    /** Une adresse se donne à la création et ne change plus : un lien envoyé reste valable. */
    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->token ??= self::newToken($application->company);
        });
    }

    /** « acme-k3x9p2 » : lisible par l'entreprise, impossible à deviner pour les autres. */
    public static function newToken(string $company): string
    {
        $base = Str::limit(Str::slug($company), 60, '') ?: 'candidature';

        do {
            $token = $base.'-'.Str::lower(Str::random(6));
        } while (self::withTrashed()->where('token', $token)->exists());

        return $token;
    }

    /** La page s'ouvre-t-elle avec son lien (en ligne et pas expirée) ? */
    public function isOpen(): bool
    {
        return $this->status === JobApplicationStatus::Active
            && ($this->expires_on === null || ! $this->expires_on->isPast());
    }

    /** @return BelongsTo<JobProfile, $this> */
    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'job_application_project')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** @return HasMany<JobApplicationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(JobApplicationEvent::class);
    }

    /**
     * Les projets choisis, dans l'ordre donné.
     *
     * @param  list<int>  $projectIds
     */
    public function syncProjects(array $projectIds): void
    {
        $this->projects()->sync(collect(array_values(array_unique($projectIds)))
            ->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index]])
            ->all());
    }
}
