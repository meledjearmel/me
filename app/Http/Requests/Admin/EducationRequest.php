<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:255'],
            'degree.fr' => ['required', 'string', 'max:255'],
            'degree.en' => ['required', 'string', 'max:255'],
            'field.fr' => ['required', 'string', 'max:255'],
            'field.en' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description.fr' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ];
    }
}
