<?php

namespace App\Http\Requests\Admin;

use App\Models\EstimatorItem;
use Illuminate\Foundation\Http\FormRequest;

class EstimatorSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rates = [];

        foreach (EstimatorItem::CURRENCIES as $currency) {
            // Vide ou 0 : la devise n'est pas proposée au visiteur.
            $rates["estimator_hourly_rates.{$currency}"] = ['nullable', 'integer', 'min:0', 'max:10000000'];
        }

        return [
            'estimator_hourly_rates' => ['present', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            ...$rates,
            'estimator_risk_percent' => ['required', 'integer', 'min:0', 'max:200'],
            'estimator_hours_per_week' => ['required', 'integer', 'min:1', 'max:80'],
        ];
    }
}
