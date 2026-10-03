<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_type' => $this->whenLoaded('appointmentType', fn (): ?array => $this->appointmentType === null ? null : [
                'id' => $this->appointmentType->id,
                /** @var array{fr: string, en: string} */
                'name' => $this->appointmentType->getTranslations('name'),
                'duration_minutes' => $this->appointmentType->duration_minutes,
            ]),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            /** Lieu : `video`, `phone`, `whatsapp` ou `in_person`. */
            'location' => $this->location,
            'message' => $this->message,
            /** Début et fin, en heure d'Abidjan (UTC). */
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            /** Fuseau horaire du visiteur, pour lui parler dans son heure. */
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            /** Statut : `pending`, `confirmed`, `declined` ou `cancelled`. */
            'status' => $this->status,
            /** Lien visio, adresse ou note envoyés au visiteur à la confirmation. */
            'meeting_details' => $this->meeting_details,
            'decline_reason' => $this->decline_reason,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
        ];
    }
}
