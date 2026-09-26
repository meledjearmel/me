<?php

namespace App\Http\Middleware;

use App\Models\MusicGenre;
use App\Models\Profile;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'musicUrl' => fn () => Profile::query()->first()?->getFirstMediaUrl('music') ?: null,
            'playlist' => fn () => $request->is('admin*') ? [] : $this->playlist(),
            'locale' => fn () => app()->getLocale(),
        ];
    }

    /**
     * Les registres du lecteur avec leurs pistes jouables (celles qui ont un fichier audio).
     *
     * @return list<array{id: int, key: string, label: string, tracks: list<array{id: int, title: string, artist: string|null, url: string}>}>
     */
    private function playlist(): array
    {
        return MusicGenre::query()
            ->with(['tracks' => fn ($query) => $query->orderBy('sort_order')->with('media')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MusicGenre $genre): array => [
                'id' => $genre->id,
                'key' => $genre->key,
                'label' => $genre->label,
                'tracks' => $genre->tracks
                    ->filter(fn ($track) => $track->audioUrl() !== null)
                    ->map(fn ($track): array => [
                        'id' => $track->id,
                        'title' => $track->title,
                        'artist' => $track->artist,
                        'url' => $track->audioUrl(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $genre): bool => $genre['tracks'] !== [])
            ->values()
            ->all();
    }
}
