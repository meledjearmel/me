<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReviewInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Facultatifs : ils préremplissent le formulaire de la personne invitée.
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            /** Langue du lien et du formulaire : `fr` ou `en`. */
            'locale' => ['required', Rule::in(['fr', 'en'])],
            // Sans rattachement, l'avis est général.
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'experience_id' => ['nullable', 'integer', 'exists:experiences,id'],
            'education_id' => ['nullable', 'integer', 'exists:educations,id'],
            /** Mémo visible seulement dans l'admin. */
            'note' => ['nullable', 'string', 'max:255'],
            /** Date après laquelle le lien ne fonctionne plus ; vide = sans limite. */
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Les données validées ; une date d'expiration vaut jusqu'à la fin de ce jour-là.
     *
     * @return array<string, mixed>
     */
    public function invitationData(): array
    {
        $data = $this->validated();

        if (isset($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['expires_at'])->endOfDay();
        }

        return $data;
    }
}
