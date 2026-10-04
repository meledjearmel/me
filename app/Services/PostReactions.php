<?php

namespace App\Services;

use App\Enums\PostReactionType;
use App\Jobs\SendPushNotification;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Réactions anonymes aux articles. Le lecteur est reconnu par un cookie aléatoire dont seule
 * l'empreinte est stockée : ni compte, ni IP.
 */
class PostReactions
{
    public const string COOKIE = 'blog_reader';

    /** Durée de vie du cookie du lecteur, en minutes (deux ans). */
    private const int COOKIE_MINUTES = 60 * 24 * 365 * 2;

    /** Empreinte du lecteur, ou null quand il n'a pas encore de cookie. */
    public function readerHash(Request $request): ?string
    {
        $reader = $request->cookie(self::COOKIE);

        return is_string($reader) && $reader !== '' ? $this->hash($reader) : null;
    }

    /** Empreinte du lecteur, en lui donnant un cookie s'il n'en a pas encore. */
    public function ensureReaderHash(Request $request): string
    {
        if (($hash = $this->readerHash($request)) !== null) {
            return $hash;
        }

        $reader = Str::random(40);
        Cookie::queue(self::COOKIE, $reader, self::COOKIE_MINUTES);

        return $this->hash($reader);
    }

    /** Ajoute la réaction du lecteur, ou la retire s'il l'avait déjà donnée. */
    public function toggle(Post $post, PostReactionType $type, string $readerHash): void
    {
        $deleted = $post->reactions()->where('type', $type)->where('reader_hash', $readerHash)->delete();

        if ($deleted === 0) {
            $post->reactions()->firstOrCreate(['type' => $type, 'reader_hash' => $readerHash]);
            $this->notify($post);
        }
    }

    /**
     * Notification push des réactions reçues : au plus une par article sur le délai réglé
     * pour les félicitations (`congratulation_notify_minutes`, 0 = à chaque réaction).
     */
    private function notify(Post $post): void
    {
        $minutes = SiteSetting::current()->congratulation_notify_minutes;

        if ($minutes > 0 && ! Cache::add("post-reactions:notified:{$post->id}", true, now()->addMinutes($minutes))) {
            return;
        }

        $total = array_sum($post->reactionCounts());

        SendPushNotification::dispatch(
            'Nouvelle réaction',
            sprintf('%d %s sur « %s »', $total, $total > 1 ? 'réactions' : 'réaction', $post->getTranslation('title', 'fr')),
            ['type' => 'post_reaction', 'id' => (string) $post->id],
        );
    }

    /**
     * Compteur de chaque réaction et celles du lecteur.
     *
     * @return array{counts: array<string, int>, mine: list<string>}
     */
    public function summary(Post $post, ?string $readerHash): array
    {
        return [
            'counts' => $post->reactionCounts(),
            'mine' => $readerHash === null ? [] : $post->reactions()
                ->where('reader_hash', $readerHash)
                ->pluck('type')
                ->map(fn (PostReactionType $type): string => $type->value)
                ->values()
                ->all(),
        ];
    }

    private function hash(string $reader): string
    {
        return hash_hmac('sha256', $reader, (string) config('app.key'));
    }
}
