@php
    $location = $appointment->location->value;
    $locationLabel = $isEnglish
        ? ['video' => 'Video call', 'phone' => 'Phone call', 'whatsapp' => 'WhatsApp call', 'in_person' => 'In person'][$location]
        : ['video' => 'Visio', 'phone' => 'Appel téléphonique', 'whatsapp' => 'Appel WhatsApp', 'in_person' => 'En présentiel'][$location];
@endphp
<x-mail::message>
@if ($isEnglish)
# Hello {{ $appointment->name }},

@switch($kind)
@case('confirmed')
Good news: our appointment is **confirmed**. You will find a calendar invitation attached to add it to your calendar.
@break
@case('declined')
Thank you for your request. Unfortunately, I am not available for this appointment.
@break
@case('cancelled')
Your appointment has been cancelled, as you asked. Thank you for letting me know.
@break
@case('reminder')
A quick reminder of our appointment, coming up soon.
@break
@default
Thank you for your request. I have received it and will confirm it as soon as possible; you will receive another email then.
@endswitch

<x-mail::table>
| | |
|:--|:--|
@if ($typeName)
| **Appointment** | {{ $typeName }} |
@endif
| **When** | {{ $when }} ({{ $timezone }}) |
| **Where** | {{ $locationLabel }} |
</x-mail::table>

@if (in_array($kind, ['confirmed', 'reminder'], true) && $appointment->meeting_details)
**Details:** {{ $appointment->meeting_details }}
@endif
@if ($kind === 'declined' && $appointment->decline_reason)
> {{ $appointment->decline_reason }}
@endif
@if (in_array($kind, ['declined', 'cancelled'], true))
<x-mail::button :url="$bookingUrl">
Book another time slot
</x-mail::button>
@endif

If you would like to add anything, simply reply to this email, it comes straight to me.

Talk soon,<br>
**{{ $ownerName }}**

@if ($cancelUrl)
<x-mail::subcopy>
Can't make it? [Cancel this appointment]({{ $cancelUrl }}) to free the slot.
</x-mail::subcopy>
@endif
@else
# Bonjour {{ $appointment->name }},

@switch($kind)
@case('confirmed')
Bonne nouvelle : notre rendez-vous est **confirmé**. Vous trouverez en pièce jointe une invitation pour l'ajouter à votre agenda.
@break
@case('declined')
Merci pour votre demande. Malheureusement, je ne suis pas disponible pour ce rendez-vous.
@break
@case('cancelled')
Votre rendez-vous est annulé, comme vous l'avez demandé. Merci de m'avoir prévenu.
@break
@case('reminder')
Un petit rappel de notre rendez-vous, qui approche.
@break
@default
Merci pour votre demande. Je l'ai bien reçue et je la confirme au plus vite : vous recevrez alors un nouvel email.
@endswitch

<x-mail::table>
| | |
|:--|:--|
@if ($typeName)
| **Rendez-vous** | {{ $typeName }} |
@endif
| **Quand** | {{ $when }} ({{ $timezone }}) |
| **Où** | {{ $locationLabel }} |
</x-mail::table>

@if (in_array($kind, ['confirmed', 'reminder'], true) && $appointment->meeting_details)
**Détails :** {{ $appointment->meeting_details }}
@endif
@if ($kind === 'declined' && $appointment->decline_reason)
> {{ $appointment->decline_reason }}
@endif
@if (in_array($kind, ['declined', 'cancelled'], true))
<x-mail::button :url="$bookingUrl">
Choisir un autre créneau
</x-mail::button>
@endif

Si vous souhaitez ajouter quelque chose, répondez simplement à cet email : il m'arrive directement.

À très vite,<br>
**{{ $ownerName }}**

@if ($cancelUrl)
<x-mail::subcopy>
Un empêchement ? [Annulez ce rendez-vous]({{ $cancelUrl }}) pour libérer le créneau.
</x-mail::subcopy>
@endif
@endif
</x-mail::message>
