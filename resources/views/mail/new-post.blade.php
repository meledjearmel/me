<x-mail::message>
@if ($isEnglish)
# {{ $title }}

@if ($excerpt)
{{ $excerpt }}
@endif

<x-mail::button :url="$postUrl">
Read the article ({{ $readingMinutes }} min)
</x-mail::button>

Talk soon,<br>
**{{ $ownerName }}**

<small>You receive this email because you subscribed to my blog. [Unsubscribe]({{ $unsubscribeUrl }})</small>
@else
# {{ $title }}

@if ($excerpt)
{{ $excerpt }}
@endif

<x-mail::button :url="$postUrl">
Lire l'article ({{ $readingMinutes }} min)
</x-mail::button>

À très vite,<br>
**{{ $ownerName }}**

<small>Vous recevez cet email car vous êtes inscrit à mon blog. [Se désinscrire]({{ $unsubscribeUrl }})</small>
@endif
</x-mail::message>
