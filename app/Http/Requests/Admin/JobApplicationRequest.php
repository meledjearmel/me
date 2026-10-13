<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobApplicationStatus;
use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobApplicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            // Le profil dont le CV est proposé au téléchargement (aucun : pas de CV).
            'job_profile_id' => ['nullable', 'integer', Rule::exists('job_profiles', 'id')->where('status', PublicationStatus::Published->value)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'intro' => ['required', 'string', 'max:5000'],
            'job_posting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'job_posting_text' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(JobApplicationStatus::class)],
            'expires_on' => ['nullable', 'date_format:Y-m-d'],
            // Les projets montrés, dans l'ordre (6 au plus).
            'project_ids' => ['nullable', 'array', 'max:6'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at')],
        ];
    }
}
