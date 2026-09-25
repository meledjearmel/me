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
                'cv_files' => collect(Profile::CV_LOCALES)
                    ->mapWithKeys(function (string $locale) use ($profile): array {
                        $media = $profile->getFirstMedia(Profile::cvFileCollection($locale));

                        return [$locale => $media === null ? null : [
                            'file_name' => $media->file_name,
                            'url' => $media->getUrl(),
                        ]];
                    }),
            ],
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $profile = Profile::query()->firstOrFail();

        $profile->update($request->safe()->except(['photo', 'cv_photo', 'cv_file_fr', 'cv_file_en', 'music']));

        foreach (Profile::CV_LOCALES as $locale) {
            if ($request->hasFile('cv_file_'.$locale)) {
                $profile->addMediaFromRequest('cv_file_'.$locale)->toMediaCollection(Profile::cvFileCollection($locale));
            }
        }

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

    /** Retire le CV uploadé d'une langue : le CV redevient généré. */
    public function destroyCv(string $locale): RedirectResponse
    {
        Profile::query()->firstOrFail()->clearMediaCollection(Profile::cvFileCollection($locale));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CV retiré.')]);

        return to_route('admin.profile.edit');
    }
}
