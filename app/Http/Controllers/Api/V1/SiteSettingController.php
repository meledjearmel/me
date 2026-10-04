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
     * Réglages de gestion du site
     *
     * Site, avis, CV, notifications et rendez-vous. Les heures des rendez-vous sont celles d'Abidjan (UTC).
     */
    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Modifier les réglages de gestion du site
     *
     * Chaque champ est facultatif : ceux qui ne sont pas envoyés gardent leur valeur.
     */
    public function update(SiteSettingRequest $request): JsonResponse
    {
        SiteSetting::current()->update($request->validated());

        return response()->json($this->payload());
    }

    /**
     * @return array{contact_opens_drawer: bool, testimonial_video_enabled: bool, cv_job_profile_id: int|null, cv_source: string, congratulation_notify_minutes: int, booking_enabled: bool, booking_min_notice_hours: int, booking_horizon_days: int, booking_buffer_minutes: int, booking_video_provider: string, booking_video_link: string|null}
     */
    private function payload(): array
    {
        $settings = SiteSetting::current();

        return [
            /** Le bouton « Contact » ouvre le tiroir latéral (`true`) ou mène à la page Contact (`false`). */
            'contact_opens_drawer' => $settings->contact_opens_drawer,
            /** Les visiteurs peuvent joindre ou filmer une vidéo avec leur avis. */
            'testimonial_video_enabled' => $settings->testimonial_video_enabled,
            /** Profil métier dont le CV est téléchargeable sur le site (`null` : le premier publié). */
            'cv_job_profile_id' => $settings->cv_job_profile_id,
            /** Source prioritaire du CV : `uploaded` (PDF importé) ou `generated`, l'autre en repli. */
            'cv_source' => $settings->cv_source->value,
            /** Au plus une notification push de félicitations par motif sur ce nombre de minutes (0 = à chaque envoi). */
            'congratulation_notify_minutes' => $settings->congratulation_notify_minutes,
            /** La prise de rendez-vous est ouverte sur le site. */
            'booking_enabled' => $settings->booking_enabled,
            'booking_min_notice_hours' => $settings->booking_min_notice_hours,
            'booking_horizon_days' => $settings->booking_horizon_days,
            'booking_buffer_minutes' => $settings->booking_buffer_minutes,
            /** Visio : `jitsi` (un lien Jitsi unique créé à la confirmation) ou `link` (le lien fixe `booking_video_link`). */
            'booking_video_provider' => $settings->booking_video_provider,
            'booking_video_link' => $settings->booking_video_link,
        ];
    }
}
