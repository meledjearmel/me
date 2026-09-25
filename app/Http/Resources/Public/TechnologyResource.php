<?php

namespace App\Http\Resources\Public;

use App\Models\Technology;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'icon' => $this->icon,
            'icon_light_url' => $this->iconUrl('light'),
            'icon_dark_url' => $this->iconUrl('dark'),
        ];
    }

    /**
     * URL de l'icône pour un thème (bibliothèque dashboard-icons, copiée dans
     * public/icons/tech). On prend `{icone}-{theme}` s'il existe (logo adapté à ce
     * thème), sinon `{icone}`, en svg ou en png, sinon null.
     */
    private function iconUrl(string $theme): ?string
    {
        if ($this->icon === null) {
            return null;
        }

        foreach (["{$this->icon}-{$theme}", $this->icon] as $file) {
            foreach (['svg', 'png'] as $extension) {
                $path = public_path("icons/tech/{$file}.{$extension}");

                if (is_file($path)) {
                    // La date du fichier dans l'URL : une icône remplacée
                    // n'est jamais servie depuis l'ancien cache du navigateur.
                    return asset("icons/tech/{$file}.{$extension}").'?v='.filemtime($path);
                }
            }
        }

        return null;
    }
}
