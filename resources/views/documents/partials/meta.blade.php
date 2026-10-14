@if ($document->number)
    <strong>{{ $document->number }}</strong><br>
@else
    <span class="draft">{{ $en ? 'DRAFT' : 'BROUILLON' }}</span><br>
@endif
Date : {{ $date($document->issued_at ?? now()) }}<br>
@if ($isQuote && $document->valid_until)
    {{ $en ? 'Valid until' : 'Valable jusqu’au' }} : {{ $date($document->valid_until) }}<br>
@endif
@if (! $isQuote && $document->due_at)
    {{ $en ? 'Due date' : 'Échéance' }} : {{ $date($document->due_at) }}<br>
@endif
@if ($document->source?->number)
    {{ $en ? 'Ref.' : 'Réf.' }} : {{ $document->source->number }}
@endif
