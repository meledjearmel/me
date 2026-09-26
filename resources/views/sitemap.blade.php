{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach ($locales as $locale)
    <url>
        <loc>{{ $baseUrl }}/{{ $locale }}{{ $entry['path'] }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
@foreach ($locales as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate }}" href="{{ $baseUrl }}/{{ $alternate }}{{ $entry['path'] }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $baseUrl }}/{{ $locales[0] }}{{ $entry['path'] }}"/>
    </url>
@endforeach
@endforeach
</urlset>
