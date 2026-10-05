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
     * Chaque champ est facultatif : ceux qui ne sont pas envoyés gardent leur valeur. `now_content` peut
     * contenir des mentions `@[App Station](project:12)` (voir `GET /v1/posts/mentions`).
     */
    public function update(SiteSettingRequest $request): JsonResponse
    {
        $settings = SiteSetting::current();
        $settings->update($request->safe()->except('now_content'));

        if ($request->has('now_content')) {
            $settings->updateNowContent($request->validated('now_content', []));
        }

        return response()->json($this->payload());
    }

    /**
     * @return array{contact_opens_drawer: bool, availability_status: string, available_from: string|null, now_content: array<string, string|null>|null, now_updated_at: string|null, testimonial_video_enabled: bool, blog_enabled: bool, blog_reactions_enabled: bool, blog_comments_enabled: bool, cv_job_profile_id: int|null, cv_source: string, congratulation_notify_minutes: int, booking_enabled: bool, booking_min_notice_hours: int, booking_horizon_days: int, booking_buffer_minutes: int, booking_video_provider: string, booking_video_link: string|null}
     */
    private function payload(): array
    {
        $settings = SiteSetting::current();

        return [
            /** Le bouton « Contact » ouvre le tiroir latéral (`true`) ou mène à la page Contact (`false`). */
            'contact_opens_drawer' => $settings->contact_opens_drawer,
            /**
             * Disponibilité affichée sur le site : `available`, `from` (à partir de `available_from`,
             * puis « disponible » une fois la date passée) ou `unavailable`.
             */
            'availability_status' => $settings->availability_status->value,
            /** Date de disponibilité (AAAA-MM-JJ), utilisée avec le statut `from`. */
            'available_from' => $settings->available_from?->toDateString(),
            /**
             * Page « Now » : texte libre (une ligne vide entre deux paragraphes, « - » en début
             * de ligne pour une liste). Vide en français, la page n'est pas affichée.
             *
             * @var array{fr?: string|null, en?: string|null}|null
             */
            'now_content' => $settings->now_content,
            /** Dernière modification du texte de la page « Now ». */
            'now_updated_at' => $settings->now_updated_at?->toIso8601String(),
            /** Les visiteurs peuvent joindre ou filmer une vidéo avec leur avis. */
            'testimonial_video_enabled' => $settings->testimonial_video_enabled,
            /** Le blog est affiché sur le site public (pages, navigation, plan du site). */
            'blog_enabled' => $settings->blog_enabled,
            /** Les lecteurs peuvent réagir aux articles (j’aime, j’adore, impressionnant, instructif, réflexion), sans compte, une fois par réaction. */
            'blog_reactions_enabled' => $settings->blog_reactions_enabled,
            /** Les lecteurs peuvent commenter les articles ; un commentaire n'est affiché qu'une fois approuvé. Désactivé, le formulaire et les commentaires disparaissent. */
            'blog_comments_enabled' => $settings->blog_comments_enabled,
            /** Profil métier dont le CV est téléchargeable sur le site (`null` : le premier publié). */
            'cv_job_profile_id' => $settings->cv_job_profile_id,
            /** Source prioritaire du CV : `uploaded` (PDF importé) ou `generated`, l'autre en repli. */
            'cv_source' => $settings->cv_source->value,
            /** Au plus une notification push de félicitations par motif, et de réactions par article du blog, sur ce nombre de minutes (0 = à chaque envoi). Chaque commentaire est notifié. */
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
