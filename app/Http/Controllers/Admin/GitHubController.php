<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GitHubSelectionRequest;
use App\Models\SiteSetting;
use App\Services\GitHubActivity;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Choix des dépôts GitHub de la page À propos, parmi ceux de la dernière synchronisation.
 */
class GitHubController extends Controller
{
    public function __construct(private GitHubActivity $github) {}

    public function edit(): Response
    {
        $cached = $this->github->cached();

        return Inertia::render('admin/github/edit', [
            'available' => $cached['available'] ?? [],
            'selected' => SiteSetting::current()->github_repositories ?? [],
            'syncedAt' => $cached['synced_at'] ?? null,
            'hasToken' => $this->github->hasToken(),
            'maxSelected' => GitHubActivity::MAX_SELECTED,
        ]);
    }

    public function update(GitHubSelectionRequest $request): RedirectResponse
    {
        SiteSetting::current()->update(['github_repositories' => array_values($request->validated('repositories'))]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dépôts GitHub enregistrés.')]);

        return back();
    }

    /** Synchronisation immédiate, sans attendre le passage horaire du planificateur. */
    public function sync(): RedirectResponse
    {
        $synced = $this->github->sync();

        Inertia::flash('toast', $synced
            ? ['type' => 'success', 'message' => __('Dépôts GitHub synchronisés.')]
            : ['type' => 'error', 'message' => __('GitHub n’a pas répondu : la dernière version est conservée.')]);

        return back();
    }
}
