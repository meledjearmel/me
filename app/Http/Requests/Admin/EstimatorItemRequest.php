<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPricing;
use App\Models\EstimatorItem;
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
        $isFeature = $this->input('kind') === EstimatorItemKind::Feature->value;
        $isPercent = $isFeature && $this->input('pricing') === EstimatorPricing::Percent->value;

        return [
            'kind' => ['required', Rule::enum(EstimatorItemKind::class)],
            'name.fr' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'description.fr' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            // Une option en pourcentage n'a pas d'heures.
            'hours_min' => [$isPercent ? 'nullable' : 'required', 'integer', 'min:0', 'max:5000'],
            'hours_max' => [$isPercent ? 'nullable' : 'required', 'integer', $isPercent ? 'min:0' : 'gte:hours_min', 'max:5000'],
            'pricing' => [$isFeature ? 'required' : 'nullable', Rule::enum(EstimatorPricing::class)],
            'percent' => [$isPercent ? 'required' : 'nullable', 'integer', 'min:1', 'max:500'],
            // Ignoré pour une option, qui suit le mode du type de projet.
            'billing' => ['nullable', 'required_if:kind,'.EstimatorItemKind::ProjectType->value, Rule::enum(EstimatorBilling::class)],
            // Option : réservée à ces types de projet (aucun : proposée pour tous).
            'project_type_ids' => ['nullable', 'array'],
            'project_type_ids.*' => ['integer', Rule::exists('estimator_items', 'id')->where('kind', EstimatorItemKind::ProjectType->value)->whereNull('deleted_at')],
            // Taux : ceux de l'élément, sinon ceux de sa grille, sinon le taux global.
            'estimator_rate_id' => ['nullable', 'integer', Rule::exists('estimator_rates', 'id')],
            'hourly_rates' => ['nullable', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'hourly_rates.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
