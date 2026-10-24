<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une proposition envoyée sur une plateforme freelance (Upwork, Malt…). Privée : elle ne
 * s'affiche jamais sur le site, elle sert d'historique et d'exemples à l'IA qui rédige les suivantes.
 */
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    /** @var list<string> */
    public const array PLATFORMS = ['upwork', 'malt', 'fiverr', 'freelancer', 'linkedin', 'other'];

    /** @var list<string> */
    protected $fillable = [
        'platform',
        'locale',
        'job_title',
        'client_name',
        'job_url',
        'job_posting',
        'body',
        'screening_answers',
        'bid',
        'connects',
        'status',
        'notes',
        'drafted_by',
        'sent_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => ProposalStatus::class,
        'connects' => 'integer',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // La date d'envoi se pose d'elle-même au premier statut qui n'est plus un brouillon.
        static::saving(function (self $proposal): void {
            if ($proposal->sent_at === null && $proposal->status !== ProposalStatus::Draft) {
                $proposal->sent_at = now();
            }
        });
    }

    /**
     * Les meilleurs exemples d'abord (mission obtenue, entretien, vue, envoyée, brouillon, refusée), puis les plus récents.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function bestFirst(Builder $query): void
    {
        $query->orderByRaw("case status when 'hired' then 0 when 'interview' then 1 when 'viewed' then 2 when 'sent' then 3 when 'draft' then 4 else 5 end")
            ->latest();
    }
}
