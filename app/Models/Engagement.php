<?php

namespace App\Models;

use App\Enums\EngagementOutcome;
use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Enums\EstimatorPeriod;
use Database\Factories\EngagementFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Demande de collaboration reçue depuis le site : un projet freelance à confier
 * ou un recrutement (dans ce cas, le CV adapté au poste est envoyé par email).
 */
class Engagement extends Model
{
    /** @use HasFactory<EngagementFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'type',
        'name',
        'email',
        'company',
        'subject',
        'job_profile_id',
        'contract',
        'budget_type',
        'budget_amount',
        'budget_currency',
        'budget_period',
        'timeline',
        'estimate',
        'message',
        'locale',
        'status',
        'outcome',
        'outcome_amount',
        'outcome_currency',
        'outcome_at',
        'cv_sent_at',
        'ip_address',
        'user_agent',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => EngagementType::class,
        'status' => EngagementStatus::class,
        'outcome' => EngagementOutcome::class,
        'outcome_amount' => 'integer',
        'outcome_at' => 'datetime',
        'cv_sent_at' => 'datetime',
        'estimate' => 'array',
        'ai_analysis' => 'array',
        'ai_analyzed_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $appends = ['budget_label', 'estimate_label'];

    /**
     * Enregistre l'issue de la demande : signée (avec le montant et la devise signés), perdue,
     * ou aucune (null efface l'issue). Une issue range aussi la demande parmi les traitées.
     */
    public function recordOutcome(?EngagementOutcome $outcome, ?int $amount = null, ?string $currency = null): void
    {
        $isWon = $outcome === EngagementOutcome::Won;

        $this->update([
            'outcome' => $outcome,
            'outcome_amount' => $isWon ? $amount : null,
            'outcome_currency' => $isWon ? $currency : null,
            'outcome_at' => $outcome === null ? null : now(),
            ...($outcome === null ? [] : ['status' => EngagementStatus::Handled]),
        ]);
    }

    /** Budget lisible : « 5 000 CHF (forfait) » ou « 80 EUR / heure ». */
    protected function budgetLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->budget_amount === null) {
                return null;
            }

            $amount = number_format($this->budget_amount, 0, ',', ' ').' '.$this->currencySymbol();

            if ($this->budget_type === 'period') {
                $unit = ['hour' => 'heure', 'day' => 'jour', 'week' => 'semaine', 'month' => 'mois', 'year' => 'an'][$this->budget_period] ?? $this->budget_period;

                return "{$amount} / {$unit}";
            }

            return "{$amount} (forfait)";
        });
    }

    /**
     * Estimation lisible quand la demande vient de l'estimateur : « 3 600 – 6 000 CHF (forfait,
     * 36 – 60 h) · 2 semaines » ou « 36 – 60 h × 100 CHF/h ≈ 3 600 – 6 000 CHF · 2 semaines ».
     */
    protected function estimateLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            $estimate = $this->estimate;

            if (! is_array($estimate) || ! isset($estimate['price_min'], $estimate['hours_min'])) {
                return null;
            }

            $number = fn (int $value): string => number_format($value, 0, ',', ' ');
            $currency = self::currencyLabel($estimate['currency'] ?? null);
            $prices = "{$number($estimate['price_min'])} – {$number($estimate['price_max'])} {$currency}";
            $hours = "{$estimate['hours_min']} – {$estimate['hours_max']} h";
            $weeks = $estimate['weeks_min'] === $estimate['weeks_max']
                ? $estimate['weeks_min'].' semaine'.($estimate['weeks_min'] > 1 ? 's' : '')
                : "{$estimate['weeks_min']} à {$estimate['weeks_max']} semaines";

            $amount = ($estimate['billing'] ?? 'fixed') === 'hourly'
                ? "{$hours} × {$number($estimate['hourly_rate'])} {$currency}/h ≈ {$prices}"
                : "{$prices} (forfait, {$hours})";

            // Options récurrentes, hors total : « + Maintenance 300 – 450 CHF/mois ».
            $recurring = collect($estimate['recurring'] ?? [])
                ->map(fn (array $line): string => " + {$line['label']} {$number($line['price_min'])} – {$number($line['price_max'])} {$currency}/".(EstimatorPeriod::tryFrom($line['period'])?->label() ?? $line['period']))
                ->implode('');

            return "{$amount} · {$weeks}{$recurring}";
        });
    }

    private function currencySymbol(): string
    {
        return self::currencyLabel($this->budget_currency);
    }

    private static function currencyLabel(?string $currency): string
    {
        return $currency === 'XOF' ? 'FCFA' : (string) $currency;
    }

    /** @return BelongsTo<JobProfile, $this> */
    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }
}
