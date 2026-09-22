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

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'headline',
        'bio_short',
        'bio_full',
        'email',
        'phone',
        'location',
        'social_links',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }
}
