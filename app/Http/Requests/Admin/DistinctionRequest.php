<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Models\Distinction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistinctionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contextTable = match ($this->input('context_type')) {
            'experience' => 'experiences',
            'education' => 'educations',
            'project' => 'projects',
            default => null,
        };

        return [
            'title.fr' => ['required', 'string', 'max:160'],
            'title.en' => ['required_unless:status,draft', 'nullable', 'string', 'max:160'],
            'issuer' => ['required', 'string', 'max:120'],
            'description.fr' => ['nullable', 'string', 'max:500'],
            'description.en' => ['nullable', 'string', 'max:500'],
            'awarded_on' => ['required', 'date', 'before_or_equal:today'],
            'url' => ['nullable', 'url', 'max:255'],
            /** Contexte : `experience`, `education` ou `project` ; vide pour aucun. */
            'context_type' => ['nullable', 'required_with:context_id', Rule::in(array_keys(Distinction::CONTEXTS))],
            /** Identifiant de l'expérience, de la formation ou du projet. */
            'context_id' => [
                'nullable',
                'required_with:context_type',
                'integer',
                ...($contextTable !== null ? [Rule::exists($contextTable, 'id')->whereNull('deleted_at')] : []),
            ],
            'status' => ['sometimes', Rule::enum(PublicationStatus::class)],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
