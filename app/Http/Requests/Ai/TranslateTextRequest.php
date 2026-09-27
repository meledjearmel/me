<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class TranslateTextRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:'.config('ai.text_assist.max_text_length')],
            'source_locale' => ['required', 'string', 'in:fr,en'],
            'target_locale' => ['required', 'string', 'in:fr,en', 'different:source_locale'],
        ];
    }
}
