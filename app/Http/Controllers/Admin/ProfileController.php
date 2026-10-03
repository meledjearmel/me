<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $profile = Profile::query()->firstOrFail();

        return Inertia::render('admin/profile/edit', [
            'profile' => [
                ...$profile->toArray(),
                'photo_url' => $profile->getFirstMediaUrl('photo') ?: null,
                'cv_photo_url' => $profile->getFirstMediaUrl('cv_photo') ?: null,
                'music' => ($music = $profile->getFirstMedia('music')) === null ? null : [
                    'file_name' => $music->file_name,
                    'url' => $music->getUrl(),
                ],
            ],
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $profile = Profile::query()->firstOrFail();

        $profile->update($request->safe()->except(['photo', 'cv_photo', 'music']));

        if ($request->hasFile('music')) {
            $profile->addMediaFromRequest('music')->toMediaCollection('music');
        }

        if ($request->hasFile('photo')) {
            $profile->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        if ($request->hasFile('cv_photo')) {
            $profile->addMediaFromRequest('cv_photo')->toMediaCollection('cv_photo');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil mis à jour.')]);

        return to_route('admin.profile.edit');
    }

    /** Retire la bande audio uploadée : le lecteur revient à la piste par défaut. */
    public function destroyMusic(): RedirectResponse
    {
        Profile::query()->firstOrFail()->clearMediaCollection('music');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bande audio retirée.')]);

        return to_route('admin.profile.edit');
    }
}
