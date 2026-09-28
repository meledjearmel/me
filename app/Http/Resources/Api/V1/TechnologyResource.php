<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Technology;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Technology
 */
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
            'category_id' => $this->category_id,
            'category' => new TechnologyCategoryResource($this->category),
            'icon' => $this->icon,
            'icon_light_url' => $library->url($this->icon, 'light'),
            'icon_dark_url' => $library->url($this->icon, 'dark'),
            'description' => $this->getTranslations('description'),
        ];
    }
}
