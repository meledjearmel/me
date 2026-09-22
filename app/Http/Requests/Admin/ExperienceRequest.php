<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'role.fr' => ['required', 'string', 'max:255'],
            'role.en' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description.fr' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'sort_order' => ['integer'],
            'highlights' => ['array'],
            'highlights.*.id' => ['nullable', 'integer', 'exists:experience_highlights,id'],
            'highlights.*.text.fr' => ['required', 'string'],
            'highlights.*.text.en' => ['required', 'string'],
            'highlights.*.sort_order' => ['integer'],
        ];
    }
}
