<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title.fr' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($this->route('project'))],
            'tagline.fr' => ['nullable', 'string', 'max:160'],
            'tagline.en' => ['nullable', 'string', 'max:160'],
            'role.fr' => ['nullable', 'string', 'max:120'],
            'role.en' => ['nullable', 'string', 'max:120'],
            'client.fr' => ['nullable', 'string', 'max:120'],
            'client.en' => ['nullable', 'string', 'max:120'],
            'platform.fr' => ['nullable', 'string', 'max:120'],
            'platform.en' => ['nullable', 'string', 'max:120'],
            'context.fr' => ['required', 'string'],
            'context.en' => ['required', 'string'],
            'challenges.fr' => ['nullable', 'string'],
            'challenges.en' => ['nullable', 'string'],
            'realization.fr' => ['required', 'string'],
            'realization.en' => ['required', 'string'],
            'decisions' => ['array', 'max:6'],
            'decisions.*.choice.fr' => ['required', 'string', 'max:160'],
            'decisions.*.choice.en' => ['required', 'string', 'max:160'],
            'decisions.*.reason.fr' => ['required', 'string', 'max:600'],
            'decisions.*.reason.en' => ['required', 'string', 'max:600'],
            // Mois (AAAA-MM) : la fiche projet n'affiche pas le jour.
            'started_on' => ['nullable', 'date_format:Y-m'],
            'ended_on' => ['nullable', 'date_format:Y-m', 'after_or_equal:started_on'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'result.fr' => ['required', 'string'],
            'result.en' => ['required', 'string'],
            'key_figures' => ['array', 'max:4'],
            'key_figures.*.value' => ['required', 'string', 'max:20'],
            'key_figures.*.label.fr' => ['required', 'string', 'max:80'],
            'key_figures.*.label.en' => ['required', 'string', 'max:80'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'is_featured' => ['boolean'],
            'is_open_source' => ['boolean'],
            'status' => ['required', 'in:published,archived'],
            'sort_order' => ['integer'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['array'],
            'gallery.*' => ['image', 'max:5120'],
            'domains' => ['array'],
            'domains.*' => ['integer', 'exists:domains,id'],
            'job_profiles' => ['array'],
            'job_profiles.*' => ['integer', 'exists:job_profiles,id'],
            'technologies' => ['array'],
            'technologies.*' => ['integer', 'exists:technologies,id'],
            'related_projects' => ['array'],
            'related_projects.*' => ['integer', 'exists:projects,id'],
        ];
    }
}
