<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CelebrationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message.fr' => ['required', 'string', 'max:280'],
            'message.en' => ['required', 'string', 'max:280'],
            'button_label.fr' => ['required', 'string', 'max:40'],
            'button_label.en' => ['required', 'string', 'max:40'],
            'congratulated_for' => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'weight' => ['integer', 'min:1', 'max:100'],
            'chance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'delay_seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'display_seconds' => ['required', 'integer', 'min:5', 'max:120'],
        ];
    }
}
