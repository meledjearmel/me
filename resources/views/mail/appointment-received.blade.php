@php
    $locationLabel = ['video' => 'Visio', 'phone' => 'Téléphone', 'whatsapp' => 'WhatsApp', 'in_person' => 'En présentiel'][$appointment->location->value];
@endphp
<x-mail::message>
@if ($event === 'cancelled')
# Rendez-vous annulé

**{{ $appointment->name }}**{{ $appointment->company ? ' ('.$appointment->company.')' : '' }} a annulé son rendez-vous. Le créneau est de nouveau libre.
@else
# Nouvelle demande de rendez-vous

**{{ $appointment->name }}**{{ $appointment->company ? ' ('.$appointment->company.')' : '' }} souhaite un rendez-vous ({{ $appointment->locale === 'en' ? 'version anglaise' : 'version française' }}).
@endif

<x-mail::table>
| | |
|:--|:--|
@if ($appointment->appointmentType)
| **Type** | {{ $appointment->appointmentType->getTranslation('name', 'fr') }} |
@endif
| **Quand** | {{ $when }} (heure d'Abidjan) |
| **Lieu** | {{ $locationLabel }} |
| **Email** | {{ $appointment->email }} |
@if ($appointment->phone)
| **Téléphone** | {{ $appointment->phone }} |
@endif
@if ($appointment->timezone)
| **Fuseau du visiteur** | {{ $appointment->timezone }} |
@endif
</x-mail::table>

@if ($event === 'cancelled')
<x-mail::button :url="route('admin.appointments.show', $appointment)">
Voir le rendez-vous
</x-mail::button>
@else
@if ($appointment->message)
> {{ $appointment->message }}
@endif

Le créneau est bloqué en attendant votre réponse.

<x-mail::button :url="route('admin.appointments.show', $appointment)">
Confirmer ou refuser
</x-mail::button>
@endif
</x-mail::message>
