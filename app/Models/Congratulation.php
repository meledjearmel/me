<?php

namespace App\Models;

use App\Enums\CongratulationSource;
use App\Jobs\SendPushNotification;
use Database\Factories\CongratulationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Un envoi de félicitations depuis le site public (les clics d'un visiteur sont
 * regroupés côté navigateur), avec son motif.
 */
class Congratulation extends Model
{
    /** @use HasFactory<CongratulationFactory> */
    use HasFactory;

    /**
     * Délai par défaut entre deux notifications push d'un même motif, quand le profil n'en
     * fixe pas : un visiteur enthousiaste ne doit pas faire vibrer le téléphone en boucle.
     */
    public const DEFAULT_NOTIFY_EVERY_MINUTES = 10;

    /** Motif enregistré pour la carte « Distinction » de la page À propos. */
    public const ABOUT_REASON = 'Carte « Distinction » de la page À propos';

    /** « Félicité pour » de la carte « Distinction » de la page À propos. */
    public const ABOUT_CONGRATULATED_FOR = 'votre distinction de meilleur agent du CIAPOL';

    /** @var list<string> */
    protected $fillable = [
        'source',
        'celebration_id',
        'distinction_id',
        'reason',
        'count',
        'locale',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'source' => CongratulationSource::class,
        'count' => 'integer',
    ];

    /** @return BelongsTo<Celebration, $this> */
    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    /** @return BelongsTo<Distinction, $this> */
    public function distinction(): BelongsTo
    {
        return $this->belongsTo(Distinction::class);
    }

    /** Enregistre un envoi et prévient l'app mobile (au plus une fois par motif toutes les NOTIFY_EVERY_MINUTES). */
    public static function record(CongratulationSource $source, int $count, ?Celebration $celebration = null, ?Distinction $distinction = null): self
    {
        $congratulation = static::query()->create([
            'source' => $source,
            'celebration_id' => $celebration?->id,
            'distinction_id' => $distinction?->id,
            'reason' => $celebration?->getTranslation('message', 'fr')
                ?? $distinction?->getTranslation('title', 'fr')
                ?? self::ABOUT_REASON,
            'count' => $count,
            'locale' => app()->getLocale(),
        ]);

        $reasonKey = match (true) {
            $celebration !== null => $celebration->id,
            $distinction !== null => 'distinction:'.$distinction->id,
            default => $source->value,
        };

        if (static::shouldNotify($reasonKey)) {
            SendPushNotification::dispatch(
                'Nouvelles félicitations 🎉',
                static::notificationBody($count, $celebration, $distinction),
                ['type' => 'congratulation', 'id' => (string) $congratulation->id],
            );
        }

        return $congratulation;
    }

    /**
     * Au plus une notification par motif sur le délai réglé dans le profil
     * (`congratulation_notify_minutes`, 0 = une notification à chaque envoi).
     */
    private static function shouldNotify(int|string $reasonKey): bool
    {
        $minutes = SiteSetting::current()->congratulation_notify_minutes ?? self::DEFAULT_NOTIFY_EVERY_MINUTES;

        if ($minutes <= 0) {
            return true;
        }

        return Cache::add('congratulations:notified:'.$reasonKey, true, now()->addMinutes($minutes));
    }

    /** « Vous avez reçu 3 félicitations pour votre prix de meilleur agent » (toujours en français : c'est Armel qui la lit). */
    public static function notificationBody(int $count, ?Celebration $celebration = null, ?Distinction $distinction = null): string
    {
        $for = match (true) {
            $celebration !== null => $celebration->congratulated_for ?? Str::limit($celebration->getTranslation('message', 'fr'), 100),
            $distinction !== null => sprintf('votre distinction « %s »', $distinction->getTranslation('title', 'fr')),
            default => self::ABOUT_CONGRATULATED_FOR,
        };

        return sprintf('Vous avez reçu %d %s pour %s', $count, $count > 1 ? 'félicitations' : 'félicitation', $for);
    }
}
