<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', Rule::unique('job_profiles', 'key')->ignore($this->route('job_profile'))],
            'label.fr' => ['required', 'string', 'max:255'],
            'label.en' => ['required', 'string', 'max:255'],
            'description.fr' => ['required', 'string'],
            'description.en' => ['required', 'string'],
            'cv_description.fr' => ['required', 'string'],
            'cv_description.en' => ['required', 'string'],
            'sort_order' => ['integer'],
        ];
    }
}
