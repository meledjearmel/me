<?php

namespace App\Http\Requests;

use App\Enums\AppointmentLocation;
use App\Models\AppointmentType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentBookingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $location = AppointmentLocation::tryFrom((string) $this->input('location'));

        return [
            'appointment_type_id' => ['required', 'integer', Rule::exists('appointment_types', 'id')->where('is_active', true)->whereNull('deleted_at')],
            // Le lieu doit faire partie de ceux que propose ce type de rendez-vous.
            'location' => ['required', Rule::enum(AppointmentLocation::class), function (string $attribute, mixed $value, Closure $fail): void {
                $type = AppointmentType::query()->find($this->integer('appointment_type_id'));

                if ($type !== null && ! $type->locations->contains(AppointmentLocation::tryFrom((string) $value))) {
                    $fail(__('Ce lieu n’est pas proposé pour ce rendez-vous.'));
                }
            }],
            // Début du créneau, en ISO 8601 (UTC) : c'est le serveur qui vérifie qu'il est libre.
            'starts_at' => ['required', 'date'],
            'timezone' => ['nullable', 'timezone:all'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            // Indispensable pour un appel téléphonique ou WhatsApp.
            'phone' => [$location?->needsPhone() ? 'required' : 'nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            // Piège anti-spam : un humain ne le voit ni ne le remplit jamais.
            'website' => ['prohibited'],
        ];
    }
}
