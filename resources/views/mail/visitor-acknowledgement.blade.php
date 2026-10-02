<x-mail::message>
@if ($isEnglish)
# Hello {{ $visitorName }},

@switch($kind)
@case('freelance')
Thank you for telling me about your project. I have received your request and will read it carefully before getting back to you.
@break
@case('hiring')
Thank you for reaching out about this position. I have received your message and will get back to you as soon as possible.
@break
@default
Thank you for your message. It has reached me safely and I will get back to you as soon as possible.
@endswitch

In the meantime, you can see how I work through my projects: the context, the technical choices and the results.

<x-mail::button :url="$projectsUrl">
See my projects
</x-mail::button>

If you would like to add anything, simply reply to this email, it comes straight to me.

Talk soon,<br>
**{{ $ownerName }}**
@else
# Bonjour {{ $visitorName }},

@switch($kind)
@case('freelance')
Merci de m'avoir présenté votre projet. J'ai bien reçu votre demande et je la lis attentivement avant de revenir vers vous.
@break
@case('hiring')
Merci de m'avoir contacté au sujet de ce poste. J'ai bien reçu votre message et je reviens vers vous dans les meilleurs délais.
@break
@default
Merci pour votre message. Il m'est bien parvenu et je vous réponds dans les meilleurs délais.
@endswitch

En attendant, vous pouvez découvrir ma façon de travailler à travers mes projets : le contexte, les choix techniques et les résultats.

<x-mail::button :url="$projectsUrl">
Voir mes projets
</x-mail::button>

Si vous souhaitez ajouter quelque chose, répondez simplement à cet email : il m'arrive directement.

À très vite,<br>
**{{ $ownerName }}**
@endif
</x-mail::message>
