<?php

namespace App\Http\Requests\Admin;

use App\Enums\CvSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cv_last_name' => ['nullable', 'string', 'max:255'],
            'cv_first_name' => ['nullable', 'string', 'max:255'],
            'headline.fr' => ['required', 'string', 'max:255'],
            'headline.en' => ['required', 'string', 'max:255'],
            'bio_short.fr' => ['required', 'string'],
            'bio_short.en' => ['required', 'string'],
            'bio_full.fr' => ['required', 'string'],
            'bio_full.en' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'social_links.github' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            // Facultatif : un client qui ne l'envoie pas garde la valeur actuelle.
            'congratulation_notify_minutes' => ['sometimes', 'integer', 'min:0', 'max:1440'],
            // Profil métier dont le CV est téléchargeable sur le site ; `null` : le premier profil publié.
            'cv_job_profile_id' => ['sometimes', 'nullable', 'integer', 'exists:job_profiles,id'],
            // Source prioritaire du CV (téléchargement et envoi aux recruteurs) ; l'autre sert de repli.
            'cv_source' => ['sometimes', Rule::enum(CvSource::class)],
            'testimonial_video_enabled' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'cv_photo' => ['nullable', 'image', 'max:5120'],
            'music' => ['nullable', 'file', 'mimes:mp3,ogg,wav,m4a,aac', 'max:20480'],
        ];
    }
}
