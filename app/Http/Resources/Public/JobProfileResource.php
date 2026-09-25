<?php

namespace App\Http\Resources\Public;

use App\Models\JobProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobProfile */
class JobProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $label = $this->getTranslation('label', $locale);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $label,
            'hero_title' => $this->getTranslation('hero_title', $locale, false) ?: $label,
            'hero_words' => str($this->getTranslation('hero_words', $locale, false) ?? '')
                ->explode(',')
                ->map(fn (string $word): string => trim($word))
                ->filter()
                ->take(3)
                ->values(),
        ];
    }
}
