<?php

namespace App\Http\Requests\Admin;

use App\Enums\EngagementOutcome;
use App\Models\EstimatorItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngagementOutcomeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // won (signée), lost (perdue) ; vide pour effacer l'issue.
            'outcome' => ['nullable', Rule::enum(EngagementOutcome::class)],
            // Montant réellement signé, hors taxes.
            'outcome_amount' => ['nullable', 'required_if:outcome,'.EngagementOutcome::Won->value, 'integer', 'min:0', 'max:100000000'],
            'outcome_currency' => ['nullable', 'required_if:outcome,'.EngagementOutcome::Won->value, Rule::in(EstimatorItem::CURRENCIES)],
        ];
    }

    public function outcome(): ?EngagementOutcome
    {
        return EngagementOutcome::tryFrom((string) $this->validated('outcome'));
    }
}
