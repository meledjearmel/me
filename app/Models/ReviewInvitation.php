<?php

namespace App\Models;

use App\Concerns\HasSubjects;
use Database\Factories\ReviewInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Invitation à laisser un avis : un lien qui ouvre le formulaire d'avis prérempli, rattaché
 * à aucun, un ou plusieurs projets, expériences et formations. Personnel (une utilisation)
 * ou collectif (plusieurs personnes, avec ou sans limite : `max_uses` vide).
 */
class ReviewInvitation extends Model
{
    /** @use HasFactory<ReviewInvitationFactory> */
    use HasFactory, HasSubjects;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'locale',
        'note',
        'max_uses',
        'expires_at',
    ];

    /** @var list<string> */
    protected $hidden = ['token'];

    /** @var list<string> */
    protected $appends = ['url', 'status'];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReviewInvitation $invitation): void {
            $invitation->token ??= Str::random(64);
        });
    }

    /**
     * Invitations encore utilisables : ni épuisées ni expirées.
     *
     * @param  Builder<ReviewInvitation>  $query
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->whereNull('used_at')
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** @return HasMany<Testimonial, $this> */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** Lien collectif : plusieurs personnes peuvent laisser leur avis par ce même lien. */
    public function isCollective(): bool
    {
        return $this->max_uses !== 1;
    }

    /** Compte un avis reçu ; le lien est épuisé (`used_at`) une fois sa limite atteinte. */
    public function recordUse(): void
    {
        $this->uses_count++;

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            $this->used_at = now();
        }

        $this->save();
    }

    /** `pending` (lien valable), `used` (avis reçu ou limite atteinte) ou `expired`. */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->used_at !== null => 'used',
            $this->expires_at !== null && $this->expires_at->isPast() => 'expired',
            default => 'pending',
        });
    }

    /** Le lien à envoyer : la page des avis, qui ouvre le formulaire prérempli. */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => rtrim((string) config('app.url'), '/')."/{$this->locale}/testimonials?invitation={$this->token}");
    }
}
