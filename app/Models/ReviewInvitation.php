<?php

namespace App\Models;

use Database\Factories\ReviewInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Invitation personnelle à laisser un avis : un lien à usage unique, éventuellement
 * rattaché à un projet, une expérience ou une formation, qui ouvre le formulaire
 * d'avis prérempli.
 */
class ReviewInvitation extends Model
{
    /** @use HasFactory<ReviewInvitationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'locale',
        'project_id',
        'experience_id',
        'education_id',
        'note',
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
    ];

    protected static function booted(): void
    {
        static::creating(function (ReviewInvitation $invitation): void {
            $invitation->token ??= Str::random(64);
        });
    }

    /**
     * Invitations encore utilisables : ni utilisées ni expirées.
     *
     * @param  Builder<ReviewInvitation>  $query
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->whereNull('used_at')
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Experience, $this> */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    /** @return BelongsTo<Education, $this> */
    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    /** @return BelongsTo<Testimonial, $this> */
    public function testimonial(): BelongsTo
    {
        return $this->belongsTo(Testimonial::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** `pending` (lien valable), `used` (avis reçu) ou `expired`. */
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

    /** Ce dont parle l'avis, dans la langue donnée, ou null pour un avis général. */
    public function subjectLabel(string $locale): ?string
    {
        if ($this->project) {
            return $this->project->getTranslation('title', $locale);
        }

        if ($this->experience) {
            return $this->experience->getTranslation('role', $locale).' — '.$this->experience->company;
        }

        if ($this->education) {
            return $this->education->getTranslation('degree', $locale).' — '.$this->education->institution;
        }

        return null;
    }
}
