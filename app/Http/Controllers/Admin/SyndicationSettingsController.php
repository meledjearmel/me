<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyndicationSettingsRequest;
use App\Services\Syndication\SyndicationSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les réglages de republication des articles sur dev.to et Hashnode : clés, mode, langue. */
class SyndicationSettingsController extends Controller
{
    public function edit(SyndicationSettings $settings): Response
    {
        return Inertia::render('admin/syndication/edit', [
            'settings' => $settings->toArray(),
            // Les publications du compte Hashnode, pour choisir sans chercher l'identifiant.
            'hashnodePublications' => Inertia::defer(fn (): array => $settings->hashnodePublications()),
        ]);
    }

    public function update(SyndicationSettingsRequest $request, SyndicationSettings $settings): RedirectResponse
    {
        $error = $settings->update($request->validated());

        Inertia::flash('toast', $error === null
            ? ['type' => 'success', 'message' => __('Réglages de republication enregistrés.')]
            : ['type' => 'error', 'message' => __('Réglages enregistrés, mais publication Hashnode introuvable : :error', ['error' => $error])]);

        return back();
    }
}
