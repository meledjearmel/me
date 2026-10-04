<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NowPageRequest extends FormRequest
{
    /**
     * Texte libre : une ligne vide sépare deux paragraphes, une ligne qui commence par
     * « - » forme une liste. Vide en français, la page « Now » n'est plus affichée.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'now_content.fr' => ['nullable', 'string', 'max:5000'],
            'now_content.en' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
