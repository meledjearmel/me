<?php

namespace App\Http\Requests\Admin;

use App\Enums\CvSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSettingRequest extends FormRequest
{
    /**
     * Chaque réglage est facultatif : un client (l'app) qui n'en envoie qu'une partie
     * garde les autres valeurs.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Site : le bouton « Contact » ouvre le tiroir latéral, ou mène à la page Contact.
            'contact_opens_drawer' => ['sometimes', 'boolean'],
            // Avis : les visiteurs peuvent joindre ou filmer une vidéo.
            'testimonial_video_enabled' => ['sometimes', 'boolean'],
            // CV : profil métier proposé au téléchargement (`null` : le premier publié) et source prioritaire.
            'cv_job_profile_id' => ['sometimes', 'nullable', 'integer', 'exists:job_profiles,id'],
            'cv_source' => ['sometimes', Rule::enum(CvSource::class)],
            // Notifications : au plus une notification de félicitations par motif sur ce nombre de minutes.
            'congratulation_notify_minutes' => ['sometimes', 'integer', 'min:0', 'max:1440'],
            // Rendez-vous : ouverture, délai minimum (heures), horizon (jours), pause (minutes), visio par défaut.
            'booking_enabled' => ['sometimes', 'boolean'],
            'booking_min_notice_hours' => ['sometimes', 'integer', 'min:0', 'max:720'],
            'booking_horizon_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'booking_buffer_minutes' => ['sometimes', 'integer', 'min:0', 'max:240'],
            'booking_video_link' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
