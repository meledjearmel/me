<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BookingSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            // Délai minimum entre la demande et le rendez-vous, en heures (un mois au plus).
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            // Jusqu'à combien de jours à l'avance on peut réserver.
            'horizon_days' => ['required', 'integer', 'min:1', 'max:365'],
            // Pause entre deux rendez-vous, en minutes.
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'video_link' => ['nullable', 'url', 'max:255'],
        ];
    }
}
