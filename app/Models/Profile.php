<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
        // Bande audio du site : sans fichier, le lecteur utilise la piste par défaut.
        $this->addMediaCollection('music')->singleFile();
    }

    /**
     * Version légère de la photo du site (WebP, 800 px au plus) : l'original
     * peut peser plusieurs centaines de Ko pour un affichage de quelques pixels.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('web')
            ->performOnCollections('photo')
            ->fit(Fit::Max, 800, 800)
            ->format('webp')
            ->quality(82)
            ->nonQueued();
    }

    /** URL de la photo du site : la version légère si elle existe, sinon l'original. */
    public function photoUrl(): ?string
    {
        $photo = $this->getFirstMedia('photo');

        if ($photo === null) {
            return null;
        }

        return $photo->hasGeneratedConversion('web') ? $photo->getUrl('web') : $photo->getUrl();
    }
}
