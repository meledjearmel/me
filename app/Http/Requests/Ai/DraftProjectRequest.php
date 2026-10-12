<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class DraftProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Dépôt GitHub public, « propriétaire/nom ». Un dépôt privé est refusé. */
            'github_repository' => ['nullable', 'required_without:notes', 'string', 'max:200', 'regex:~^[A-Za-z0-9-]+/[A-Za-z0-9._-]+$~'],
            /** Notes en vrac sur le projet : besoin, client, dates, chiffres… */
            'notes' => ['nullable', 'required_without:github_repository', 'string', 'max:'.config('ai.text_assist.draft.max_notes_length')],
        ];
    }
}
