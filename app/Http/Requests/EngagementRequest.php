<?php

namespace App\Http\Requests;

use App\Enums\EngagementType;
use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngagementRequest extends FormRequest
{
    /** Valeurs acceptées pour les listes de choix (le texte affiché est côté interface). */
    public const CONTRACTS = ['cdi', 'cdd', 'mission', 'other'];

    public const TIMELINES = ['urgent', 'quarter', 'semester', 'flexible'];

    public const BUDGET_TYPES = ['fixed', 'period'];

    public const BUDGET_CURRENCIES = ['CHF', 'EUR', 'USD', 'XOF'];

    public const BUDGET_PERIODS = ['hour', 'day', 'week', 'month', 'year'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isHiring = $this->input('type') === EngagementType::Hiring->value;

        return [
            'type' => ['required', Rule::enum(EngagementType::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => [$isHiring ? 'required' : 'nullable', 'string', 'max:255'],
            // Recrutement : intitulé du poste. Freelance : nature du projet.
            'subject' => ['required', 'string', 'max:255'],
            // Recrutement : le profil dont on génère le CV.
            'job_profile_id' => [$isHiring ? 'required' : 'nullable', 'integer', Rule::exists('job_profiles', 'id')->where('status', PublicationStatus::Published->value)],
            'contract' => ['nullable', Rule::in(self::CONTRACTS)],
            'budget_type' => ['nullable', Rule::in(self::BUDGET_TYPES)],
            'budget_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'budget_currency' => ['nullable', 'required_with:budget_amount', Rule::in(self::BUDGET_CURRENCIES)],
            // Tarif par période : l'unité est obligatoire quand le budget est de ce type.
            'budget_period' => ['nullable', 'required_if:budget_type,period', Rule::in(self::BUDGET_PERIODS)],
            'timeline' => ['nullable', Rule::in(self::TIMELINES)],
            'message' => [$isHiring ? 'nullable' : 'required', 'string', 'max:5000'],
            // Piège anti-spam : un humain ne le voit ni ne le remplit jamais.
            'website' => ['prohibited'],
        ];
    }
}
