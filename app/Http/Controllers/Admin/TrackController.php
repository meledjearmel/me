<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrackRequest;
use App\Models\MusicGenre;
use App\Models\Track;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/tracks/index', [
            'tracks' => $this->paginateList(Track::query()->with('genre')->orderBy('music_genre_id')->orderBy('sort_order'), $request, ['title', 'artist'], ['music_genre_id']),
            'filters' => $this->listFilters($request, ['music_genre_id']),
            'genres' => $this->genres(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tracks/create', [
            'genres' => $this->genres(),
        ]);
    }

    public function store(TrackRequest $request): RedirectResponse
    {
        $track = Track::query()->create($request->safe()->except('audio'));
        $track->addMediaFromRequest('audio')->toMediaCollection('audio');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Piste créée.')]);

        return to_route('admin.tracks.index');
    }

    public function edit(Track $track): Response
    {
        return Inertia::render('admin/tracks/edit', [
            'track' => [...$track->toArray(), 'audio_url' => $track->audioUrl()],
            'genres' => $this->genres(),
        ]);
    }

    public function update(TrackRequest $request, Track $track): RedirectResponse
    {
        $track->update($request->safe()->except('audio'));

        if ($request->hasFile('audio')) {
            $track->addMediaFromRequest('audio')->toMediaCollection('audio');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Piste mise à jour.')]);

        return to_route('admin.tracks.index');
    }

    public function destroy(Track $track): RedirectResponse
    {
        $track->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Piste supprimée.')]);

        return to_route('admin.tracks.index');
    }

    /** @return Collection<int, MusicGenre> */
    private function genres(): Collection
    {
        return MusicGenre::query()->orderBy('sort_order')->get(['id', 'key', 'label']);
    }
}
