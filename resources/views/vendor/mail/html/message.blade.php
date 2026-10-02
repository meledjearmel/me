<x-mail::layout>
{{-- En-tête : monogramme et nom, comme la pastille de navigation du site --}}
<x-slot:header>
<x-mail::header :url="$owner['portfolio']">
<span class="monogram">{{ collect(explode(' ', $owner['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
<span class="brand">{{ $owner['name'] }}</span>
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pied de page : portfolio, liens publics, mentions dans la langue de l'email --}}
<x-slot:footer>
<x-mail::footer>
[{{ $owner['portfolioLabel'] }}]({{ $owner['portfolio'] }})@foreach ($owner['links'] as $label => $url) · [{{ $label }}]({{ $url }})@endforeach<br>
{{ $owner['name'] }}@if ($owner['location']) · {{ $owner['location'] }}@endif · © {{ date('Y') }} · {{ app()->getLocale() === 'en' ? 'All rights reserved.' : 'Tous droits réservés.' }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
