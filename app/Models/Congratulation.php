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

    /** Au plus une notification push par motif sur cette durée : un visiteur enthousiaste ne doit pas faire vibrer le téléphone en boucle. */
    public const NOTIFY_EVERY_MINUTES = 10;

    /** Motif enregistré pour la carte « Distinction » de la page À propos. */
    public const ABOUT_REASON = 'Carte « Distinction » de la page À propos';

    /** « Félicité pour » de la carte « Distinction » de la page À propos. */
    public const ABOUT_CONGRATULATED_FOR = 'votre distinction de meilleur agent du CIAPOL';

    /** @var list<string> */
    protected $fillable = [
        'source',
        'celebration_id',
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

    /** Enregistre un envoi et prévient l'app mobile (au plus une fois par motif toutes les NOTIFY_EVERY_MINUTES). */
    public static function record(CongratulationSource $source, int $count, ?Celebration $celebration = null): self
    {
        $congratulation = static::query()->create([
            'source' => $source,
            'celebration_id' => $celebration?->id,
            'reason' => $celebration?->getTranslation('message', 'fr') ?? self::ABOUT_REASON,
            'count' => $count,
            'locale' => app()->getLocale(),
        ]);

        $throttleKey = 'congratulations:notified:'.($celebration?->id ?? $source->value);

        if (Cache::add($throttleKey, true, now()->addMinutes(self::NOTIFY_EVERY_MINUTES))) {
            SendPushNotification::dispatch(
                'Nouvelles félicitations 🎉',
                static::notificationBody($count, $celebration),
                ['type' => 'congratulation', 'id' => (string) $congratulation->id],
            );
        }

        return $congratulation;
    }

    /** « Vous avez reçu 3 félicitations pour votre prix de meilleur agent » (toujours en français : c'est Armel qui la lit). */
    public static function notificationBody(int $count, ?Celebration $celebration = null): string
    {
        $for = $celebration === null
            ? self::ABOUT_CONGRATULATED_FOR
            : ($celebration->congratulated_for ?? Str::limit($celebration->getTranslation('message', 'fr'), 100));

        return sprintf('Vous avez reçu %d %s pour %s', $count, $count > 1 ? 'félicitations' : 'félicitation', $for);
    }
}
