<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use App\Enums\CertificationKind;
use Database\Factories\CertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Certification ou formation courte, affichée sur la page Certifications (à part du
 * parcours académique). Le badge est une image facultative.
 */
class Certification extends Model implements HasMedia
{
    /** @use HasFactory<CertificationFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = [
        'kind',
        'name',
        'issuer',
        'issued_on',
        'expires_on',
        'credential_id',
        'credential_url',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'kind' => CertificationKind::class,
        'issued_on' => 'date:Y-m-d',
        'expires_on' => 'date:Y-m-d',
        'sort_order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('badge')->singleFile();
    }

    /** Une certification dont la date d'expiration est passée. */
    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }
}
