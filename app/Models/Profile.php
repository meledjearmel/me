<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Profile extends Model implements HasMedia
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['headline', 'bio_short', 'bio_full'];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'cv_last_name',
        'cv_first_name',
        'headline',
        'bio_short',
        'bio_full',
        'email',
        'phone',
        'location',
        'social_links',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'social_links' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        // Photo affichée sur le site.
        $this->addMediaCollection('photo')->singleFile();
        // Photo du CV : distincte de celle du site (cadrage et fond adaptés au document).
        $this->addMediaCollection('cv_photo')->singleFile();
    }
}
