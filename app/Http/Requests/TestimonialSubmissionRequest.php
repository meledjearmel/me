<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialSubmissionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            // Préremplis quand l'avis est laissé depuis un projet, une expérience ou une formation du site.
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('status', ProjectStatus::Published->value)],
            'experience_id' => ['nullable', 'integer', 'exists:experiences,id'],
            'education_id' => ['nullable', 'integer', 'exists:educations,id'],
            'content' => ['required', 'string', 'min:20', 'max:2000'],
            // Vidéo facultative, refusée si je l'ai désactivée dans l'admin (Profil). Mêmes
            // formats que dans l'admin ; 95 Mo car Cloudflare refuse les corps de plus de
            // 100 Mo. La durée (3 min) est vérifiée dans le navigateur.
            'video' => [Rule::prohibitedIf(fn (): bool => ! SiteSetting::current()->testimonial_video_enabled), 'nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp', 'max:97280'],
            // Piège anti-spam : un humain ne le voit ni ne le remplit jamais.
            'website' => ['prohibited'],
        ];
    }
}
