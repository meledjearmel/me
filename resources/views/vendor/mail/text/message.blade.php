<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="$owner['portfolio']">
            {{ $owner['name'] }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            {{ $owner['name'] }} — {{ $owner['portfolio'] }}
            © {{ date('Y') }} · {{ app()->getLocale() === 'en' ? 'All rights reserved.' : 'Tous droits réservés.' }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
