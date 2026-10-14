<?php

namespace App\Models;

use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use Database\Factories\CommercialDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Un devis, une facture proforma ou une facture. Brouillon : modifiable et sans numéro. Émis :
 * numéroté (ADEV-DEV-2026-001…) et figé. Un devis s'accepte par le lien privé du client
 * (token), ce qui marque la demande liée comme signée.
 */
class CommercialDocument extends Model
{
    /** @use HasFactory<CommercialDocumentFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'type',
        'status',
        'engagement_id',
        'source_id',
        'locale',
        'client_name',
        'client_email',
        'client_company',
        'client_address',
        'title',
        'currency',
        'lines',
        'total',
        'deposit_percent',
        'payment_terms',
        'notes',
        'valid_until',
        'due_at',
    ];

    /** @var list<string> */
    protected $hidden = ['token'];

    /** @var array<string, string> */
    protected $casts = [
        'type' => CommercialDocumentType::class,
        'status' => CommercialDocumentStatus::class,
        'lines' => 'array',
        'total' => 'integer',
        'deposit_percent' => 'integer',
        'issued_at' => 'date',
        'valid_until' => 'date',
        'due_at' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            $document->token ??= Str::random(40);
        });

        // Le total suit toujours les lignes.
        static::saving(function (self $document): void {
            $document->total = self::totalOf((array) $document->lines);
        });
    }

    /**
     * Somme des lignes, arrondie à l'unité.
     *
     * @param  list<array{quantity?: int|float|string, unit_price?: int|float|string}>  $lines
     */
    public static function totalOf(array $lines): int
    {
        return (int) round(collect($lines)->sum(fn (array $line): float => (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0)));
    }

    /** @return BelongsTo<Engagement, $this> */
    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    /** @return BelongsTo<CommercialDocument, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_id');
    }

    public function isDraft(): bool
    {
        return $this->status === CommercialDocumentStatus::Draft;
    }

    /** Montant de l'acompte demandé, arrondi à l'unité. */
    public function depositAmount(): int
    {
        return (int) round($this->total * $this->deposit_percent / 100);
    }

    /** Nom du fichier PDF : « ADEV-DEV-2026-001.pdf », ou « brouillon-12.pdf ». */
    public function filename(): string
    {
        return ($this->number ?? 'brouillon-'.$this->id).'.pdf';
    }
}
