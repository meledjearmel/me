<?php

namespace App\Http\Requests\Admin;

use App\Models\EstimatorItem;
use Illuminate\Foundation\Http\FormRequest;

class EstimatorRateRequest extends FormRequest
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
            // Vide ou 0 : la devise suit le taux global des réglages.
            'hourly_rates' => ['present', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'hourly_rates.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Données à enregistrer, sans les devises laissées vides.
     *
     * @return array<string, mixed>
     */
    public function rateData(): array
    {
        return [...$this->validated(), 'hourly_rates' => EstimatorItem::filterRates($this->validated('hourly_rates'))];
    }
}
