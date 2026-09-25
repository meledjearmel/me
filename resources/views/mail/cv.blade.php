<x-mail::message>
@if ($isEnglish)
# Hello {{ $recruiter }},

Thank you for your interest. Please find my CV attached, tailored to the position "{{ $position }}".

I would be glad to discuss it. You can simply reply to this email.

Best regards,<br>
{{ $ownerName }}
@else
# Bonjour {{ $recruiter }},

Merci de votre intérêt. Vous trouverez mon CV en pièce jointe, adapté au poste « {{ $position }} ».

Je serais ravi d'en discuter : il vous suffit de répondre à cet email.

Cordialement,<br>
{{ $ownerName }}
@endif
</x-mail::message>
