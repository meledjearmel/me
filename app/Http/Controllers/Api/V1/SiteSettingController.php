<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

/**
 * @tags Réglages du site
 */
class SiteSettingController extends Controller
{
    /**
     * Réglages d'affichage du site
     *
     * `contact_opens_drawer` : le bouton « Contact » ouvre le tiroir latéral (`true`) ou mène à la page Contact (`false`).
     */
    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Modifier les réglages d'affichage du site
     */
    public function update(SiteSettingRequest $request): JsonResponse
    {
        SiteSetting::current()->update($request->validated());

        return response()->json($this->payload());
    }

    /**
     * @return array{contact_opens_drawer: bool}
     */
    private function payload(): array
    {
        return ['contact_opens_drawer' => SiteSetting::current()->contact_opens_drawer];
    }
}
