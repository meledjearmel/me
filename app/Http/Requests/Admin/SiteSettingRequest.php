<?php

namespace App\Http\Requests\Admin;

use App\Enums\AvailabilityStatus;
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
            // Page « Now » (API) : texte français et anglais ; vide en français, la page est masquée.
            'now_content' => ['sometimes', 'array'],
            'now_content.fr' => ['nullable', 'string', 'max:5000'],
            'now_content.en' => ['nullable', 'string', 'max:5000'],
            // Disponibilité affichée : disponible, à partir d'une date (obligatoire, à venir) ou indisponible.
            'availability_status' => ['sometimes', Rule::enum(AvailabilityStatus::class)],
            'available_from' => [
                // Sans `sometimes` : un statut `from` envoyé sans date doit être refusé.
                'nullable', 'date', 'required_if:availability_status,from',
                Rule::when($this->input('availability_status') === AvailabilityStatus::From->value, 'after:today'),
            ],
            // Blog : affiché sur le site public.
            'blog_enabled' => ['sometimes', 'boolean'],
            // Réactions anonymes (emojis) sous les articles.
            'blog_reactions_enabled' => ['sometimes', 'boolean'],
            // Commentaires sous les articles, publiés après modération.
            'blog_comments_enabled' => ['sometimes', 'boolean'],
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
            // Visio : un lien Jitsi unique par rendez-vous (`jitsi`), ou le lien fixe ci-dessous (`link`).
            'booking_video_provider' => ['sometimes', Rule::in(['jitsi', 'link'])],
            'booking_video_link' => ['sometimes', 'nullable', 'url', 'max:255', 'required_if:booking_video_provider,link'],
        ];
    }
}
