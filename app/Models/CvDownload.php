<?php

namespace App\Models;

use App\Enums\CvSource;
use Database\Factories\CvDownloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un téléchargement du CV depuis le site : quel CV, d'où venait le visiteur
 * (site d'origine, campagne, pays et ville) et l'email qu'il a pu laisser.
 */
class CvDownload extends Model
{
    /** @use HasFactory<CvDownloadFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'job_profile_id',
        'locale',
        'source',
        'email',
        'country_code',
        'country',
        'city',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device',
        'visitor_hash',
    ];

    /** @var list<string> */
    protected $hidden = ['visitor_hash'];

    /** @var array<string, string> */
    protected $casts = [
        'source' => CvSource::class,
    ];

    /** @return BelongsTo<JobProfile, $this> */
    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }

    /**
     * Provenance lisible : la campagne si elle est connue, sinon le site
     * d'origine, sinon un accès direct (lien tapé, favori, application).
     */
    public function origin(): string
    {
        return $this->utm_source ?? $this->referrer_host ?? 'direct';
    }
}
