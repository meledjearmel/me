@php
    $en = $document->locale === 'en';
    $money = fn (int|float $amount): string => number_format($amount, 0, ',', ' ').' '.$document->currency;
    $date = fn ($value): string => $value ? $value->format('d.m.Y') : '';
    $quantity = fn (int|float|string $value): string => rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    $units = $en ? ['hour' => 'h', 'day' => 'day', 'flat' => 'flat'] : ['hour' => 'h', 'day' => 'jour', 'flat' => 'forfait'];
    $isQuote = $document->type === \App\Enums\CommercialDocumentType::Quote;
@endphp
<!DOCTYPE html>
<html lang="{{ $document->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->type->label($document->locale) }} {{ $document->number }}</title>
    <style>
        @page { margin: 36px 40px 50px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; line-height: 1.5; color: #1c1c1c; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .issuer-name { font-size: 15px; font-weight: bold; color: #0f2747; }
        .muted { color: #666; }
        .doc-type { text-align: right; font-size: 20px; font-weight: bold; color: #0f2747; text-transform: uppercase; letter-spacing: 1px; }
        .doc-meta { text-align: right; margin-top: 4px; }
        .draft { color: #b45309; font-weight: bold; }
        .parties { margin-top: 26px; }
        .parties td { width: 50%; vertical-align: top; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 3px; }
        .client { border-left: 3px solid #ffda3f; padding-left: 10px; }
        .title { margin: 24px 0 10px; font-size: 12px; font-weight: bold; color: #0f2747; }
        .lines th { background: #0f2747; color: #fff9e9; text-align: left; padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .lines td { padding: 7px 8px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .desc { color: #666; font-size: 8.5px; }
        .totals { width: 45%; margin: 12px 0 0 55%; }
        .totals td { padding: 4px 8px; }
        .grand td { border-top: 2px solid #0f2747; font-size: 12px; font-weight: bold; color: #0f2747; padding-top: 7px; }
        .section { margin-top: 18px; page-break-inside: avoid; }
        .section p { margin: 0; }
        .accept { margin-top: 18px; padding: 10px 12px; background: #fff9e9; border: 1px solid #ffda3f; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 7.5px; color: #888; }
    </style>
</head>
<body>
    <div class="footer">
        {{ collect([$issuer['name'], $issuer['legal_form'], $issuer['registration_number'] ? ($en ? 'Reg. no. ' : 'N° ').$issuer['registration_number'] : null, $vatMention])->filter()->implode(' · ') }}
    </div>

    <table class="head">
        <tr>
            <td>
                <div class="issuer-name">{{ $issuer['name'] }}</div>
                @if ($issuer['legal_form'])<div class="muted">{{ $issuer['legal_form'] }}</div>@endif
                @if ($issuer['address'])<div>{!! nl2br(e($issuer['address'])) !!}</div>@endif
                @if ($issuer['email'])<div>{{ $issuer['email'] }}</div>@endif
                @if ($issuer['phone'])<div>{{ $issuer['phone'] }}</div>@endif
                @if ($issuer['registration_number'])<div class="muted">{{ $en ? 'Registration no.' : 'N° d’entreprise' }} : {{ $issuer['registration_number'] }}</div>@endif
            </td>
            <td>
                <div class="doc-type">{{ $document->type->label($document->locale) }}</div>
                <div class="doc-meta">
                    @if ($document->number)
                        <strong>{{ $document->number }}</strong><br>
                    @else
                        <span class="draft">{{ $en ? 'DRAFT' : 'BROUILLON' }}</span><br>
                    @endif
                    {{ $en ? 'Date' : 'Date' }} : {{ $date($document->issued_at ?? now()) }}<br>
                    @if ($isQuote && $document->valid_until)
                        {{ $en ? 'Valid until' : 'Valable jusqu’au' }} : {{ $date($document->valid_until) }}<br>
                    @endif
                    @if (! $isQuote && $document->due_at)
                        {{ $en ? 'Due date' : 'Échéance' }} : {{ $date($document->due_at) }}<br>
                    @endif
                    @if ($document->source?->number)
                        {{ $en ? 'Ref.' : 'Réf.' }} : {{ $document->source->number }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td></td>
            <td>
                <div class="client">
                    <div class="label">{{ $en ? 'Client' : 'Client' }}</div>
                    @if ($document->client_company)<strong>{{ $document->client_company }}</strong><br>@endif
                    {{ $document->client_name }}<br>
                    @if ($document->client_address){!! nl2br(e($document->client_address)) !!}<br>@endif
                    @if ($document->client_email){{ $document->client_email }}@endif
                </div>
            </td>
        </tr>
    </table>

    <div class="title">{{ $document->title }}</div>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ $en ? 'Description' : 'Désignation' }}</th>
                <th class="num">{{ $en ? 'Qty' : 'Qté' }}</th>
                <th class="num">{{ $en ? 'Unit price' : 'Prix unitaire' }}</th>
                <th class="num">{{ $en ? 'Amount' : 'Montant' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lines as $line)
                <tr>
                    <td>
                        {{ $line['label'] }}
                        @if (! empty($line['description']))<div class="desc">{!! nl2br(e($line['description'])) !!}</div>@endif
                    </td>
                    <td class="num">{{ $quantity($line['quantity']) }} {{ $units[$line['unit'] ?? 'flat'] ?? '' }}</td>
                    <td class="num">{{ $money((float) $line['unit_price']) }}</td>
                    <td class="num">{{ $money(round((float) $line['quantity'] * (float) $line['unit_price'])) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="grand">
            <td>{{ $en ? 'Total' : 'Total' }}</td>
            <td class="num">{{ $money($document->total) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="muted">{{ $vatMention }}</td>
        </tr>
        @if ($document->deposit_percent > 0)
            <tr>
                <td>{{ $en ? 'Deposit' : 'Acompte' }} ({{ $document->deposit_percent }} %)</td>
                <td class="num">{{ $money($document->depositAmount()) }}</td>
            </tr>
        @endif
    </table>

    @if ($document->payment_terms)
        <div class="section">
            <div class="label">{{ $en ? 'Payment terms' : 'Conditions de paiement' }}</div>
            <p>{!! nl2br(e($document->payment_terms)) !!}</p>
        </div>
    @endif

    @if ($issuer['iban'])
        <div class="section">
            <div class="label">{{ $en ? 'Bank details' : 'Coordonnées bancaires' }}</div>
            <p>IBAN : {{ $issuer['iban'] }}@if ($issuer['bank']) · {{ $issuer['bank'] }}@endif<br>
            {{ $en ? 'Reference' : 'Référence' }} : {{ $document->number ?? '—' }}</p>
        </div>
    @endif

    @if ($document->notes)
        <div class="section">
            <div class="label">{{ $en ? 'Notes' : 'Remarques' }}</div>
            <p>{!! nl2br(e($document->notes)) !!}</p>
        </div>
    @endif

    @if ($acceptUrl && $document->number)
        <div class="accept">
            {{ $en ? 'To accept this quote online:' : 'Pour accepter ce devis en ligne :' }} {{ $acceptUrl }}
        </div>
    @endif
</body>
</html>
