<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce qui se passe sur une page de candidature : une consultation (une par visiteur et par
 * jour) ou un téléchargement du CV, avec le pays, la ville et l'appareil.
 */
class JobApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    public const VIEW = 'view';

    public const CV_DOWNLOAD = 'cv_download';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'country_code',
        'country',
        'city',
        'device',
        'referrer_host',
        'visitor_hash',
    ];

    /** @var list<string> */
    protected $hidden = ['visitor_hash'];

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }
}
