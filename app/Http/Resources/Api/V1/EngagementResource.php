<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Engagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Engagement
 */
class EngagementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'subject' => $this->subject,
            'job_profile' => $this->whenLoaded('jobProfile', fn (): ?array => $this->jobProfile === null ? null : [
                'id' => $this->jobProfile->id,
                /** @var array{fr: string, en: string} */
                'label' => $this->jobProfile->getTranslations('label'),
            ]),
            'contract' => $this->contract,
            'budget' => $this->budget_label,
            /**
             * Estimation affichée au visiteur, quand la demande vient de l'estimateur de projet.
             * `hourly_rate` est le taux du type de projet (ses taux, sinon sa grille, sinon le taux
             * global) ; `rate_grid` le nom de sa grille tarifaire, `null` sans grille ou pour les
             * demandes reçues avant l'ajout des grilles.
             *
             * @var array{billing: string, currency: string, hourly_rate: int, risk_percent: int, project_type: string, rate_grid: string|null, features: list<string>, hours_min: int, hours_max: int, price_min: int, price_max: int, weeks_min: int, weeks_max: int}|null
             */
            'estimate' => $this->estimate,
            /** La même estimation, lisible : « 3 600 – 6 000 CHF (forfait, 36 – 60 h) · 2 semaines ». */
            'estimate_label' => $this->estimate_label,
            /**
             * Analyse de l'IA pour chiffrer la demande : risques, points flous, questions à poser,
             * heures réalistes (marge comprise), facturation recommandée et prix au taux de la demande.
             *
             * @var array{summary: string, risks: list<string>, unclear_points: list<string>, questions: list<string>, hours_min: int, hours_max: int, billing: string, reasoning: string|null, price_min: int|null, price_max: int|null, currency: string|null}|null
             */
            'ai_analysis' => $this->ai_analysis,
            'ai_analyzed_at' => $this->ai_analyzed_at,
            'timeline' => $this->timeline,
            'message' => $this->message,
            'locale' => $this->locale,
            'cv_sent_at' => $this->cv_sent_at,
            /** Issue de la demande : `won` (signée), `lost` (perdue), null tant qu'elle n'en a pas. */
            'outcome' => $this->outcome,
            /** Montant signé hors taxes (seulement pour `won`). */
            'outcome_amount' => $this->outcome_amount,
            /** Devise du montant signé : `CHF`, `EUR`, `USD` ou `XOF`. */
            'outcome_currency' => $this->outcome_currency,
            /** Date à laquelle l'issue a été enregistrée. */
            'outcome_at' => $this->outcome_at,
            'created_at' => $this->created_at,
        ];
    }
}
