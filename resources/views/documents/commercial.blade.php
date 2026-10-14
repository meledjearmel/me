@php
    $en = $document->locale === 'en';
    $money = fn (int|float $amount): string => number_format($amount, 0, ',', ' ').' '.$document->currency;
    $date = fn ($value): string => $value ? $value->format('d.m.Y') : '';
    $quantity = fn (int|float|string $value): string => rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    $units = $en ? ['hour' => 'h', 'day' => 'day', 'flat' => 'flat'] : ['hour' => 'h', 'day' => 'jour', 'flat' => 'forfait'];
    $isQuote = $document->type === \App\Enums\CommercialDocumentType::Quote;
    $layout = in_array($template->layout, \App\Models\DocumentTemplate::LAYOUTS, true) ? $template->layout : 'classic';
    $primary = $template->primary_color ?: '#0f2747';
    $accent = $template->accent_color ?: '#ffda3f';
    $text = fn (string $field): ?string => trim((string) $template->getTranslation($field, $document->locale, false)) ?: null;
    $title = $text('title') ?? $document->type->label($document->locale);
    $intro = $text('intro');
    $footerText = $text('footer');
@endphp
<!DOCTYPE html>
<html lang="{{ $document->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $document->number }}</title>
    <style>
        @page { margin: {{ $layout === 'modern' ? '0 0 50px' : '36px 40px 50px' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: '{{ $template->fontFamily() }}', sans-serif; font-size: 9.5px; line-height: 1.5; color: #1c1c1c; }
        table { width: 100%; border-collapse: collapse; }
        .page { padding: {{ $layout === 'modern' ? '0 40px' : '0' }}; }
        .head td { vertical-align: top; }
        .logo { max-height: 54px; max-width: 170px; margin-bottom: 8px; }
        .issuer-name { font-size: 15px; font-weight: bold; color: {{ $primary }}; }
        .muted { color: #666; }
        .doc-type { text-align: right; font-size: 20px; font-weight: bold; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 1px; }
        .doc-meta { text-align: right; margin-top: 4px; }
        .draft { color: #b45309; font-weight: bold; }
        .band { background: {{ $primary }}; color: #fff; padding: 30px 40px 26px; margin-bottom: 26px; }
        .band .issuer-name, .band .doc-type { color: #fff; }
        .band .muted { color: rgba(255, 255, 255, 0.75); }
        .band-accent { height: 5px; background: {{ $accent }}; margin: -26px 0 26px; }
        .parties { margin-top: 26px; }
        /* Le client s'aligne sur le bloc du total, dans la colonne de droite (60 % / 40 %). */
        .parties td { vertical-align: top; }
        .parties td:first-child { width: 60%; }
        .parties td:last-child { width: 40%; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 3px; }
        .client { text-align: right; border-right: 3px solid {{ $accent }}; padding-right: 10px; }
        .title { margin: 24px 0 10px; font-size: 12px; font-weight: bold; color: {{ $primary }}; }
        .intro { margin: 0 0 12px; }
        .lines th { text-align: left; padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .lines td { padding: 7px 8px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
        .layout-classic .lines th, .layout-modern .lines th { background: {{ $primary }}; color: #fff; }
        .layout-minimal .lines th { color: {{ $primary }}; border-bottom: 1.5px solid {{ $primary }}; }
        .layout-minimal .client { border-right: 0; padding-right: 0; }
        .num, .lines th.num { text-align: right; white-space: nowrap; }
        .lines .col-label { width: 52%; }
        .lines .col-qty { width: 12%; }
        .lines .col-price, .lines .col-amount { width: 18%; }
        .desc { color: #666; font-size: 8.5px; }
        .totals { width: 40%; margin: 12px 0 0 60%; }
        .totals td { padding: 4px 8px; }
        .grand td { border-top: 2px solid {{ $primary }}; font-size: 12px; font-weight: bold; color: {{ $primary }}; padding-top: 7px; }
        .layout-modern .grand td { background: {{ $accent }}; border-top: 0; color: #1c1c1c; padding: 7px 8px; }
        .section { margin-top: 18px; page-break-inside: avoid; }
        .section p { margin: 0; }
        .accept { margin-top: 18px; padding: 10px 12px; border: 1px solid {{ $accent }}; }
        .signature { margin-top: 26px; page-break-inside: avoid; }
        .signature td { width: 50%; vertical-align: top; }
        .signature td:first-child { padding-right: 16px; }
        .signature-box { height: 60px; border: 1px solid #ccc; margin-top: 4px; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 7.5px; color: #888; }
    </style>
</head>
<body class="layout-{{ $layout }}">
    <div class="footer">
        @if ($footerText){{ $footerText }}<br>@endif
        {{ collect([$issuer['name'], $issuer['legal_form'], $issuer['registration_number'] ? ($en ? 'Reg. no. ' : 'N° ').$issuer['registration_number'] : null, $vatMention])->filter()->implode(' · ') }}
    </div>


    @if ($layout === 'modern')
        <div class="band">
            <table class="head">
                <tr>
                    <td>@include('documents.partials.issuer')</td>
                    <td>
                        <div class="doc-type">{{ $title }}</div>
                        <div class="doc-meta">@include('documents.partials.meta')</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="band-accent"></div>
    @endif

    <div class="page">
        @if ($layout !== 'modern')
            <table class="head">
                <tr>
                    <td>@include('documents.partials.issuer')</td>
                    <td>
                        <div class="doc-type">{{ $title }}</div>
                        <div class="doc-meta">@include('documents.partials.meta')</div>
                    </td>
                </tr>
            </table>
        @endif

        <table class="parties">
            <tr>
                <td></td>
                <td>
                    <div class="client">
                        <div class="label">Client</div>
                        @if ($document->client_company)<strong>{{ $document->client_company }}</strong><br>@endif
                        {{ $document->client_name }}<br>
                        @if ($document->client_address){!! nl2br(e($document->client_address)) !!}<br>@endif
                        @if ($document->client_email){{ $document->client_email }}@endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="title">{{ $document->title }}</div>
        @if ($intro)<p class="intro">{!! nl2br(e($intro)) !!}</p>@endif

        <table class="lines">
            <thead>
                <tr>
                    <th class="col-label">{{ $en ? 'Description' : 'Désignation' }}</th>
                    <th class="num col-qty">{{ $en ? 'Qty' : 'Qté' }}</th>
                    <th class="num col-price">{{ $en ? 'Unit price' : 'Prix unitaire' }}</th>
                    <th class="num col-amount">{{ $en ? 'Amount' : 'Montant' }}</th>
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
                <td>Total</td>
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

        @if ($template->show_bank_details && $issuer['iban'])
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

        @if ($template->show_signature)
            <table class="signature">
                <tr>
                    <td>
                        <div class="label">{{ $en ? 'Date' : 'Date' }}</div>
                        <div class="signature-box"></div>
                    </td>
                    <td>
                        <div class="label">{{ $en ? 'Approved, client signature' : 'Bon pour accord, signature du client' }}</div>
                        <div class="signature-box"></div>
                    </td>
                </tr>
            </table>
        @endif

        @if ($acceptUrl && $document->number)
            <div class="accept">
                {{ $en ? 'To accept this quote online:' : 'Pour accepter ce devis en ligne :' }} {{ $acceptUrl }}
            </div>
        @endif
    </div>
</body>
</html>
