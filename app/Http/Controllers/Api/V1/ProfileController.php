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
     * Avec `photo` ou `cv_photo` (images), envoyer un `POST` en `multipart/form-data` avec le champ
     * `_method=PATCH` : PHP ne lit pas les fichiers d'une requête `PATCH` directe.
     */
    public function update(ProfileRequest $request): ProfileResource
    {
        $profile = Profile::query()->firstOrFail();

        $profile->update($request->safe()->except(['photo', 'cv_photo']));

        if ($request->hasFile('photo')) {
            $profile->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        if ($request->hasFile('cv_photo')) {
            $profile->addMediaFromRequest('cv_photo')->toMediaCollection('cv_photo');
        }

        return new ProfileResource($profile->refresh());
    }
}
