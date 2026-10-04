<x-mail::message>
@if ($isEnglish)
# One last step

Thank you for subscribing to my blog. Please confirm your email address to receive my next articles.

<x-mail::button :url="$confirmUrl">
Confirm my subscription
</x-mail::button>

If you did not ask for this, simply ignore this email: you will receive nothing else.

**{{ $ownerName }}**
@else
# Plus qu'une étape

Merci de vous être inscrit à mon blog. Confirmez votre adresse pour recevoir mes prochains articles.

<x-mail::button :url="$confirmUrl">
Confirmer mon inscription
</x-mail::button>

Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email : vous ne recevrez rien d'autre.

**{{ $ownerName }}**
@endif
</x-mail::message>
