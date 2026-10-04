<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Models\JobProfile;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Réglages de gestion du site : site, avis, CV, notifications et rendez-vous. */
class SiteSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/site-settings/edit', [
            'settings' => SiteSetting::current(),
            'jobProfiles' => JobProfile::query()->orderBy('sort_order')->get(['id', 'label']),
        ]);
    }

    public function update(SiteSettingRequest $request): RedirectResponse
    {
        $settings = SiteSetting::current();
        $settings->update($request->safe()->except('now_content'));

        if ($request->has('now_content')) {
            $settings->updateNowContent($request->validated('now_content', []));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Réglages enregistrés.')]);

        return back();
    }
}
