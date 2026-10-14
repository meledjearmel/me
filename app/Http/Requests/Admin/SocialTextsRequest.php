<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Les textes du kit de partage d'un article, pour une langue. */
class SocialTextsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'texts' => ['required', 'array'],
            'texts.linkedin' => ['nullable', 'string', 'max:2800'],
            'texts.x' => ['nullable', 'string', 'max:255'],
            'texts.facebook' => ['nullable', 'string', 'max:2000'],
            'texts.whatsapp' => ['nullable', 'string', 'max:300'],
        ];
    }
}
