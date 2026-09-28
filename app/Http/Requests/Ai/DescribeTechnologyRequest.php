<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class DescribeTechnologyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Nom de la technologie à décrire. */
            'name' => ['required', 'string', 'max:100'],
            /** Libellé de sa catégorie, pour aider le modèle à situer la technologie. */
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
