<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentTypeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name.fr' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'description.fr' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'locations' => ['required', 'array', 'min:1'],
            'locations.*' => ['distinct', Rule::enum(AppointmentLocation::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
