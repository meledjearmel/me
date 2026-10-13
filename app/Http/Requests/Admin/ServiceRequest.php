<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstimatorItemKind;
use App\Enums\PublicationStatus;
use App\Enums\ServicePriceMode;
use App\Models\EstimatorItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $priceMode = $this->input('price_mode');

        return [
            'slug' => ['required', 'string', 'max:120', 'alpha_dash:ascii', Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'title.fr' => ['required', 'string', 'max:120'],
            // L'anglais est obligatoire pour publier, pas pour un brouillon.
            'title.en' => ['nullable', 'required_if:status,'.PublicationStatus::Published->value, 'string', 'max:120'],
            'tagline.fr' => ['nullable', 'string', 'max:200'],
            'tagline.en' => ['nullable', 'string', 'max:200'],
            'description.fr' => ['nullable', 'string', 'max:3000'],
            'description.en' => ['nullable', 'string', 'max:3000'],
            // Une ligne par élément compris.
            'includes.fr' => ['nullable', 'string', 'max:2000'],
            'includes.en' => ['nullable', 'string', 'max:2000'],
            'price_mode' => ['required', Rule::enum(ServicePriceMode::class)],
            // Le type de projet de l'estimateur : prix calculé et lien « Estimer mon projet ».
            'estimator_item_id' => [
                $priceMode === ServicePriceMode::Estimator->value ? 'required' : 'nullable',
                'integer',
                Rule::exists('estimator_items', 'id')->where('kind', EstimatorItemKind::ProjectType->value)->whereNull('deleted_at'),
            ],
            'manual_prices' => [$priceMode === ServicePriceMode::Manual->value ? 'required' : 'nullable', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'manual_prices.*' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'status' => ['sometimes', Rule::enum(PublicationStatus::class)],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Un prix saisi à la main doit avoir au moins un montant.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('price_mode') !== ServicePriceMode::Manual->value) {
                    return;
                }

                $amounts = array_filter((array) $this->input('manual_prices', []), fn (mixed $amount): bool => (int) $amount > 0);

                if ($amounts === []) {
                    $validator->errors()->add('manual_prices', __('Indiquez au moins un montant.'));
                }
            },
        ];
    }
}
