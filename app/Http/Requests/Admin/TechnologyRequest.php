<?php

namespace App\Http\Requests\Admin;

use App\Models\Technology;
use App\Services\TechnologyIconLibrary;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnologyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(TechnologyIconLibrary $library): array
    {
        $route = $this->route('technology');
        $currentIcon = $route instanceof Technology ? $route->icon : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', Rule::exists('technology_categories', 'id')->whereNull('deleted_at')],
            // Texte de l'infobulle affichée au survol du logo, sur la page publique.
            'description.fr' => ['nullable', 'string', 'max:150'],
            'description.en' => ['nullable', 'string', 'max:150'],
            // Le logo doit exister dans la bibliothèque ; une valeur déjà
            // enregistrée reste acceptée pour ne pas bloquer les anciennes fiches.
            'icon' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($library, $currentIcon): void {
                    if ($value !== $currentIcon && ! $library->exists($value)) {
                        $fail(__('Ce logo n\'existe pas dans la bibliothèque.'));
                    }
                },
            ],
        ];
    }
}
