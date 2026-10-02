<x-mail::message>
# Nouveau message

**{{ $contact->name }}** vous a écrit depuis le formulaire de contact du portfolio.

<x-mail::table>
| | |
|:--|:--|
| **Email** | {{ $contact->email }} |
| **Sujet** | {{ $contact->subject }} |
</x-mail::table>

> {{ $contact->message }}

<x-mail::button :url="route('admin.contacts.show', $contact)">
Voir le message
</x-mail::button>

Répondez directement à cet email : votre réponse partira à {{ $contact->name }}.
</x-mail::message>
