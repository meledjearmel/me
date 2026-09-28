<?php

namespace App\Http\Resources\Public;

use App\Models\Technology;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Technology */
class TechnologyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $library = app(TechnologyIconLibrary::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->getTranslation('label', app()->getLocale()),
            'icon' => $this->icon,
            'icon_light_url' => $library->url($this->icon, 'light'),
            'icon_dark_url' => $library->url($this->icon, 'dark'),
            // Un champ traduisible non renseigné vaut '' pour la locale, jamais null : on
            // normalise vers null pour que la page publique n'affiche pas d'infobulle vide.
            'description' => $this->getTranslation('description', app()->getLocale()) !== ''
                ? $this->getTranslation('description', app()->getLocale())
                : null,
        ];
    }
}
