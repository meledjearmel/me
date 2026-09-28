<?php

namespace App\Http\Requests\Admin;

use App\Services\TechnologyIconLibrary;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnologyIconImportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(TechnologyIconLibrary $library): array
    {
        $collections = implode('|', TechnologyIconLibrary::COLLECTIONS);

        return [
            'icon' => ['required', 'string', "regex:/^({$collections}):[a-z0-9-]+$/"],
            // Vide : le logo sert aux deux thèmes (ou se décline automatiquement s'il est monochrome).
            'theme' => ['nullable', Rule::in(TechnologyIconLibrary::THEMES)],
            // Un slug en `-light` / `-dark` serait pris pour une variante de thème.
            'slug' => [
                'required',
                'string',
                'max:60',
                'regex:/^(?!.*-(light|dark)$)[a-z0-9]+(-[a-z0-9]+)*$/',
                function (string $attribute, mixed $value, Closure $fail) use ($library): void {
                    $theme = $this->input('theme');
                    $theme = in_array($theme, TechnologyIconLibrary::THEMES, true) ? $theme : null;

                    if ($library->exists($value, $theme)) {
                        $fail(__('Ce nom de logo est déjà utilisé.'));
                    }
                },
            ],
        ];
    }
}
