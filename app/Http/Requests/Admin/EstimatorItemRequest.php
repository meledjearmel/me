<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimatorItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(EstimatorItemKind::class)],
            'name.fr' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'description.fr' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'hours_min' => ['required', 'integer', 'min:0', 'max:5000'],
            'hours_max' => ['required', 'integer', 'gte:hours_min', 'max:5000'],
            // Ignoré pour une option, qui suit le mode du type de projet.
            'billing' => ['nullable', 'required_if:kind,'.EstimatorItemKind::ProjectType->value, Rule::enum(EstimatorBilling::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
