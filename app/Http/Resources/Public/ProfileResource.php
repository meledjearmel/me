<?php

namespace App\Http\Resources\Public;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Profile */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'name' => $this->name,
            'headline' => $this->getTranslation('headline', $locale),
            'bio_short' => $this->getTranslation('bio_short', $locale),
            'bio_full' => $this->getTranslation('bio_full', $locale),
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'social_links' => $this->social_links,
            'photo_url' => $this->getFirstMediaUrl('photo') ?: null,
        ];
    }
}
