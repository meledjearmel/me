<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NowPageRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La page « Now » : ce sur quoi je travaille en ce moment.
 */
class NowPageController extends Controller
{
    public function edit(): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('admin/now-page/edit', [
            'content' => ['fr' => $settings->now_content['fr'] ?? '', 'en' => $settings->now_content['en'] ?? ''],
            'updatedAt' => $settings->now_updated_at?->toIso8601String(),
        ]);
    }

    public function update(NowPageRequest $request): RedirectResponse
    {
        SiteSetting::current()->updateNowContent($request->validated('now_content', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page « Now » enregistrée.')]);

        return back();
    }
}
