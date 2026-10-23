<?php

namespace App\Http\Requests\Admin;

use App\Concerns\SubjectValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReviewInvitationRequest extends FormRequest
{
    use SubjectValidationRules;

    protected function prepareForValidation(): void
    {
        $this->mergeLegacySubjects();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Facultatifs : ils préremplissent le formulaire de la personne invitée (lien personnel seulement).
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            /** Langue du lien et du formulaire : `fr` ou `en`. */
            'locale' => ['required', Rule::in(['fr', 'en'])],
            // Sans rattachement, l'avis est général ; sinon un ou plusieurs projets, expériences, formations.
            ...$this->subjectRules(),
            /** Lien collectif : plusieurs personnes peuvent l'utiliser (une équipe, une promotion). */
            'collective' => ['boolean'],
            /** Nombre d'avis au plus pour un lien collectif ; vide = sans limite. */
            'max_uses' => ['nullable', 'integer', 'min:2', 'max:1000'],
            /** Mémo visible seulement dans l'admin. */
            'note' => ['nullable', 'string', 'max:255'],
            /** Date après laquelle le lien ne fonctionne plus ; vide = sans limite. */
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Les données validées : une date d'expiration vaut jusqu'à la fin de ce jour-là ; un lien
     * personnel sert une fois, un lien collectif ne préremplit ni nom ni e-mail.
     *
     * @return array<string, mixed>
     */
    public function invitationData(): array
    {
        $data = Arr::except($this->validated(), ['collective', 'project_ids', 'experience_ids', 'education_ids']);

        if (isset($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['expires_at'])->endOfDay();
        }

        if ($this->boolean('collective')) {
            $data['max_uses'] ??= null;
            $data['name'] = null;
            $data['email'] = null;
        } else {
            $data['max_uses'] = 1;
        }

        return $data;
    }
}
