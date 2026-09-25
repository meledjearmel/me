<!DOCTYPE html>
<html lang="{{ $cv['locale'] }}">
<head>
    <meta charset="utf-8">
    <title>{{ trim($cv['first_name'].' '.$cv['last_name']) }} — {{ $cv['title'] }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; line-height: 1.5; color: #1c1c1c; }

        .header { background: #0f2747; color: #fff9e9; padding: 28px 40px 24px; }
        .header table { width: 100%; border-collapse: collapse; }
        .photo { width: 78px; height: 78px; border-radius: 39px; border: 3px solid #ffda3f; }
        .name { margin: 0; font-size: 24px; letter-spacing: -0.3px; }
        .first-name { font-weight: normal; }
        .last-name { font-weight: bold; text-transform: uppercase; }
        .title { margin: 4px 0 0; font-size: 13px; color: #ffda3f; }
        .headline { margin: 6px 0 0; font-size: 9.5px; color: #cfd8e6; }
        .contact { margin-top: 10px; font-size: 8.5px; color: #dfe6f1; }
        .contact span { margin-right: 14px; }

        .body { padding: 22px 40px 30px; }
        .cols { width: 100%; border-collapse: collapse; }
        .cols td { vertical-align: top; }
        .cols { page-break-inside: avoid; }
        .col-left { width: 55%; padding-right: 22px; }
        .col-right { width: 45%; }

        h2 { margin: 0 0 8px; padding-bottom: 3px; border-bottom: 2px solid #ffda3f; font-size: 10.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #0f2747; }
        .section { margin-bottom: 18px; }
        .summary { margin: 0; }

        .job { margin-bottom: 12px; page-break-inside: avoid; }
        .job-head { font-weight: bold; font-size: 10.5px; }
        .job-meta { color: #666; font-size: 8.5px; }
        ul { margin: 4px 0 0; padding-left: 14px; }
        li { margin-bottom: 2px; }

        .skill-group { margin-bottom: 9px; }
        .skill-label { font-weight: bold; color: #0f2747; }
        .item { margin-bottom: 8px; }
        .item-title { font-weight: bold; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                @if ($cv['photo'])
                    <td style="width: 96px;"><img class="photo" src="{{ $cv['photo'] }}" alt=""></td>
                @endif
                <td>
                    <p class="name"><span class="first-name">{{ $cv['first_name'] }}</span> <span class="last-name">{{ $cv['last_name'] }}</span></p>
                    <p class="title">{{ $cv['title'] }}</p>
                    @if ($cv['headline'])
                        <p class="headline">{{ $cv['headline'] }}</p>
                    @endif
                    <div class="contact">
                        <span>{{ $cv['email'] }}</span>
                        @if ($cv['phone'])<span>{{ $cv['phone'] }}</span>@endif
                        @if ($cv['location'])<span>{{ $cv['location'] }}</span>@endif
                        @foreach ($cv['links'] as $label => $url)
                            <span>{{ $label }} : {{ $url }}</span>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">
        <div class="section">
            <h2>{{ $cv['labels']['profile'] }}</h2>
            <p class="summary">{{ $cv['summary'] }}</p>
        </div>

        <div class="section">
            <h2>{{ $cv['labels']['experience'] }}</h2>
            @foreach ($cv['experiences'] as $experience)
                <div class="job">
                    <div class="job-head">{{ $experience['role'] }}</div>
                    <div class="job-meta">{{ $experience['company'] }} · {{ $experience['period'] }}@if ($experience['location']) · {{ $experience['location'] }}@endif</div>
                    @if (count($experience['highlights']) > 0)
                        <ul>
                            @foreach ($experience['highlights'] as $highlight)
                                <li>{{ $highlight }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Bloc court en deux colonnes : dompdf sait le placer d'un seul tenant. --}}
        <table class="cols">
            <tr>
                <td class="col-left">
                    <div class="section">
                        <h2>{{ $cv['labels']['skills'] }}</h2>
                        @foreach ($cv['skills'] as $group)
                            <div class="skill-group">
                                <span class="skill-label">{{ $group['label'] }} :</span>
                                {{ implode(' · ', $group['items']) }}
                            </div>
                        @endforeach
                    </div>
                </td>

                <td class="col-right">
                    <div class="section">
                        <h2>{{ $cv['labels']['education'] }}</h2>
                        @foreach ($cv['educations'] as $education)
                            <div class="item">
                                <div class="item-title">{{ $education['degree'] }}</div>
                                <div class="muted">{{ $education['institution'] }}@if ($education['year']) · {{ $education['year'] }}@endif</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="section">
                        <h2>{{ $cv['labels']['languages'] }}</h2>
                        @foreach ($cv['languages'] as $language)
                            <div class="item">{{ $language }}</div>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>

        @if (count($cv['references']) > 0)
            <div class="section">
                <h2>{{ $cv['labels']['references'] }}</h2>
                @foreach ($cv['references'] as $reference)
                    <div class="item">
                        @if (isset($reference['name']))<span class="item-title">{{ $reference['name'] }}</span>@endif
                        @if (isset($reference['role']) || isset($reference['company']))
                            <span class="muted"> — {{ implode(', ', array_filter([$reference['role'] ?? null, $reference['company'] ?? null])) }}</span>
                        @endif
                        @if (isset($reference['relationship']))<span class="muted"> ({{ $reference['relationship'] }})</span>@endif
                        @if (isset($reference['email']) || isset($reference['phone']))
                            <div class="muted">{{ implode(' · ', array_filter([$reference['email'] ?? null, $reference['phone'] ?? null])) }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($cv['projects']) > 0)
            <div class="section">
                <h2>{{ $cv['labels']['projects'] }}</h2>
                @foreach ($cv['projects'] as $project)
                    <div class="item">
                        <span class="item-title">{{ $project['title'] }}</span>
                        <span class="muted"> — {{ $project['result'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
