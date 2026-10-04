<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\CvSource;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zap\Models\Concerns\HasSchedules;

/**
 * Réglages de gestion du site, sur une seule ligne : site, avis, blog, CV,
 * notifications et rendez-vous. Porte aussi mon agenda (disponibilités, périodes
 * bloquées et créneaux des rendez-vous, via Zap).
 */
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory, HasSchedules;

    /** @var list<string> */
    protected $fillable = [
        'contact_opens_drawer',
        'testimonial_video_enabled',
        'blog_enabled',
        'blog_reactions_enabled',
        'blog_comments_enabled',
        'cv_job_profile_id',
        'cv_source',
        'congratulation_notify_minutes',
        'booking_enabled',
        'booking_min_notice_hours',
        'booking_horizon_days',
        'booking_buffer_minutes',
        'booking_video_provider',
        'booking_video_link',
        'availability_status',
        'available_from',
        'now_content',
        'now_updated_at',
        'github_repositories',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'contact_opens_drawer' => 'boolean',
        'testimonial_video_enabled' => 'boolean',
        'blog_enabled' => 'boolean',
        'blog_reactions_enabled' => 'boolean',
        'blog_comments_enabled' => 'boolean',
        'cv_source' => CvSource::class,
        'congratulation_notify_minutes' => 'integer',
        'booking_enabled' => 'boolean',
        'booking_min_notice_hours' => 'integer',
        'booking_horizon_days' => 'integer',
        'booking_buffer_minutes' => 'integer',
        'availability_status' => AvailabilityStatus::class,
        'available_from' => 'date:Y-m-d',
        'now_content' => 'array',
        'now_updated_at' => 'datetime',
        'github_repositories' => 'array',
    ];

    /**
     * Le texte de la page « Now » dans la langue demandée, en repli sur le français.
     * Null quand la page n'a pas de contenu.
     */
    public function nowText(string $locale): ?string
    {
        $content = array_filter($this->now_content ?? [], fn ($text): bool => filled($text));

        return $content[$locale] ?? $content['fr'] ?? null;
    }

    /**
     * Enregistre le texte de la page « Now » ; la date de mise à jour n'avance que si
     * le texte change.
     *
     * @param  array{fr?: string|null, en?: string|null}  $content
     */
    public function updateNowContent(array $content): void
    {
        $content = array_map(fn ($text): ?string => filled($text) ? trim((string) $text) : null, $content);

        if ($content !== ($this->now_content ?? [])) {
            $this->update(['now_content' => $content, 'now_updated_at' => now()]);
        }
    }

    /**
     * La disponibilité telle que le site l'affiche : une date passée vaut « disponible ».
     *
     * @return array{status: string, from: string|null}
     */
    public function publicAvailability(): array
    {
        if ($this->availability_status === AvailabilityStatus::From && $this->available_from?->isFuture()) {
            return ['status' => AvailabilityStatus::From->value, 'from' => $this->available_from->toDateString()];
        }

        return [
            'status' => $this->availability_status === AvailabilityStatus::Unavailable
                ? AvailabilityStatus::Unavailable->value
                : AvailabilityStatus::Available->value,
            'from' => null,
        ];
    }

    /** Les réglages, créés avec leurs valeurs par défaut au premier accès. */
    public static function current(): self
    {
        // refresh() : une ligne tout juste créée récupère les valeurs par défaut de la base.
        return static::query()->first() ?? static::query()->create()->refresh();
    }

    /**
     * Profil métier dont le CV est proposé au téléchargement sur le site.
     *
     * @return BelongsTo<JobProfile, $this>
     */
    public function cvJobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class, 'cv_job_profile_id');
    }
}
