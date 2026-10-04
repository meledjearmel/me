<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class WritePostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxHtml = config('ai.text_assist.max_html_length');

        return [
            'instruction' => ['required', 'string', 'max:500'],
            'locale' => ['required', 'string', 'in:fr,en'],
            'selection' => ['nullable', 'string', 'max:'.$maxHtml],
            'context' => ['nullable', 'string', 'max:'.$maxHtml],
            'title' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:300'],
        ];
    }
}
