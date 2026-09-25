<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Models\Profile;

/**
 * @tags Profil
 */
class ProfileController extends Controller
{
    /**
     * Lire le profil
     */
    public function show(): ProfileResource
    {
        return new ProfileResource(Profile::query()->firstOrFail());
    }

    /**
     * Modifier le profil
     *
     * Avec `photo` ou `cv_photo` (images), ou `cv_file_fr` / `cv_file_en` (CV en PDF, 10 Mo max),
     * envoyer un `POST` en `multipart/form-data` avec le champ `_method=PATCH` : PHP ne lit pas les
     * fichiers d'une requête `PATCH` directe. Un nouveau fichier remplace l'ancien.
     */
    public function update(ProfileRequest $request): ProfileResource
    {
        $profile = Profile::query()->firstOrFail();

        $profile->update($request->safe()->except(['photo', 'cv_photo', 'cv_file_fr', 'cv_file_en']));

        foreach (Profile::CV_LOCALES as $locale) {
            if ($request->hasFile('cv_file_'.$locale)) {
                $profile->addMediaFromRequest('cv_file_'.$locale)->toMediaCollection(Profile::cvFileCollection($locale));
            }
        }

        if ($request->hasFile('photo')) {
            $profile->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        if ($request->hasFile('cv_photo')) {
            $profile->addMediaFromRequest('cv_photo')->toMediaCollection('cv_photo');
        }

        return new ProfileResource($profile->refresh());
    }

    /**
     * Retirer le CV uploadé d'une langue
     *
     * Sans fichier pour une langue, le CV de l'autre langue sert de secours, sinon le CV est généré.
     */
    public function destroyCv(string $locale): ProfileResource
    {
        $profile = Profile::query()->firstOrFail();

        $profile->clearMediaCollection(Profile::cvFileCollection($locale));

        return new ProfileResource($profile->refresh());
    }
}
