<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SkillRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'domain_id' => ['required', 'exists:domains,id'],
            'name.fr' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'description.fr' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ];
    }
}
