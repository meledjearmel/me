<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'details.fr' => ['nullable', 'string'],
            'details.en' => ['nullable', 'string'],
            'technologies' => ['array'],
            'technologies.*' => ['integer', 'exists:technologies,id'],
            'sort_order' => ['integer'],
            'status' => ['sometimes', Rule::enum(PublicationStatus::class)],
        ];
    }
}
