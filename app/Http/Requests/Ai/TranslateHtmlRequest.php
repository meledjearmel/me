<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class TranslateHtmlRequest extends FormRequest
{
    /**
     * Un fragment d'article : l'éditeur découpe les longs articles en plusieurs appels.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'html' => ['required', 'string', 'max:'.config('ai.text_assist.max_html_length')],
            'source_locale' => ['required', 'string', 'in:fr,en', 'different:target_locale'],
            'target_locale' => ['required', 'string', 'in:fr,en'],
        ];
    }
}
