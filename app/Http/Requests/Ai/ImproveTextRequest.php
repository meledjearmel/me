<?php

namespace App\Http\Requests\Ai;

use App\Enums\TextTone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImproveTextRequest extends FormRequest
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
            'locale' => ['required', 'string', 'in:fr,en'],
            'tone' => ['nullable', Rule::enum(TextTone::class)],
            'instructions' => ['nullable', 'string', 'max:500'],
        ];
    }
}
