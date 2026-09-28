<?php

namespace App\Http\Requests\Admin;

use App\Services\TechnologyIconLibrary;

/** Envoi d'un fichier SVG : mêmes règles de nom et de thème que l'import du catalogue. */
class TechnologyIconUploadRequest extends TechnologyIconImportRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(TechnologyIconLibrary $library): array
    {
        $rules = parent::rules($library);

        unset($rules['icon']);

        return [
            ...$rules,
            'file' => ['required', 'file', 'extensions:svg', 'max:200'],
        ];
    }
}
