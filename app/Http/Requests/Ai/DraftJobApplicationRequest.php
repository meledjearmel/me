<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** L'offre d'emploi (son texte) et la langue de la page, pour que l'IA prépare la candidature. */
class DraftJobApplicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'job_posting' => ['required', 'string', 'min:50', 'max:20000'],
            'locale' => ['required', Rule::in(['fr', 'en'])],
        ];
    }
}
