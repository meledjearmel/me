<x-mail::message>
@foreach ($paragraphs as $paragraph)
{!! nl2br(e($paragraph)) !!}

@endforeach
@if ($acceptUrl)
<x-mail::button :url="$acceptUrl">
{{ $isEnglish ? 'View and accept the quote' : 'Consulter et accepter le devis' }}
</x-mail::button>
@endif

<x-mail::panel>
**{{ $isEnglish ? 'Attached:' : 'Pièce jointe :' }}** {{ $filename }}
</x-mail::panel>

{{ $isEnglish ? 'Best regards,' : 'Bien cordialement,' }}<br>
**{{ $ownerName }}**
</x-mail::message>
