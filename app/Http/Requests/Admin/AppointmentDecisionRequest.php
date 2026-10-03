<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Confirmer (avec le lien visio, l'adresse…) ou refuser (avec un motif) une demande de rendez-vous. */
class AppointmentDecisionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'meeting_details' => ['nullable', 'string', 'max:2000'],
            'decline_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
