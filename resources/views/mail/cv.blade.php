<x-mail::message>
@if ($isEnglish)
# Hello {{ $recruiter }},

Thank you for your interest{!! $position ? ' in the **'.e($position).'** position' : '' !!}. As promised, my CV is attached, tailored to this role.

To go further than a CV allows, my portfolio walks through each project: the context, the technical choices and the results.

<x-mail::button :url="$projectsUrl">
See my projects
</x-mail::button>

I would be glad to talk about it, by video call, phone or email. Simply reply to this message, it comes straight to me.

<x-mail::panel>
**Attached:** {{ $filename }}<br>
@if ($phone)
**Phone:** {{ $phone }}
@endif
</x-mail::panel>

Looking forward to hearing from you,<br>
**{{ $ownerName }}**
@else
# Bonjour {{ $recruiter }},

Merci pour votre intérêt{!! $position ? ' pour le poste **'.e($position).'**' : '' !!}. Comme promis, vous trouverez mon CV en pièce jointe, adapté à ce profil.

Pour aller plus loin qu'un CV, mon portfolio présente chaque projet en détail : le contexte, les choix techniques et les résultats.

<x-mail::button :url="$projectsUrl">
Voir mes projets
</x-mail::button>

Je serais ravi d'en discuter, en visio, par téléphone ou par email. Il vous suffit de répondre à ce message, il m'arrive directement.

<x-mail::panel>
**Pièce jointe :** {{ $filename }}<br>
@if ($phone)
**Téléphone :** {{ $phone }}
@endif
</x-mail::panel>

Au plaisir d'échanger,<br>
**{{ $ownerName }}**
@endif
</x-mail::message>
