<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MusicGenreRequest;
use App\Models\MusicGenre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MusicGenreController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/music-genres/index', [
            'genres' => $this->paginateList(MusicGenre::query()->withCount('tracks')->orderBy('sort_order'), $request, ['key', 'label->fr', 'label->en']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/music-genres/create');
    }

    public function store(MusicGenreRequest $request): RedirectResponse
    {
        MusicGenre::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registre créé.')]);

        return to_route('admin.music-genres.index');
    }

    public function edit(MusicGenre $musicGenre): Response
    {
        return Inertia::render('admin/music-genres/edit', [
            'genre' => $musicGenre,
        ]);
    }

    public function update(MusicGenreRequest $request, MusicGenre $musicGenre): RedirectResponse
    {
        $musicGenre->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registre mis à jour.')]);

        return to_route('admin.music-genres.index');
    }

    public function destroy(MusicGenre $musicGenre): RedirectResponse
    {
        $musicGenre->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registre supprimé.')]);

        return to_route('admin.music-genres.index');
    }
}
