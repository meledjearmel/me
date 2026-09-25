<?php

namespace App\Models;

use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
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
        'message',
        'locale',
        'status',
        'cv_sent_at',
        'ip_address',
        'user_agent',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => EngagementType::class,
        'status' => EngagementStatus::class,
        'cv_sent_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $appends = ['budget_label'];

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

    private function currencySymbol(): string
    {
        return $this->budget_currency === 'XOF' ? 'FCFA' : (string) $this->budget_currency;
    }

    /** @return BelongsTo<JobProfile, $this> */
    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }
}
