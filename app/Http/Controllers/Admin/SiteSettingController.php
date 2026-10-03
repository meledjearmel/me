<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/site-settings/edit', [
            'settings' => SiteSetting::current(),
        ]);
    }

    public function update(SiteSettingRequest $request): RedirectResponse
    {
        SiteSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Réglages enregistrés.')]);

        return back();
    }
}
