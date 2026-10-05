{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Blog - {{ $profile->name }}</title>
        <link>{{ $baseUrl }}/{{ $locale }}/blog</link>
        <atom:link href="{{ $baseUrl }}/{{ $locale }}/blog/feed" rel="self" type="application/rss+xml"/>
        <description>{{ $profile->getTranslation('headline', $locale) }}</description>
        <language>{{ $locale }}</language>
@if ($posts->isNotEmpty())
        <lastBuildDate>{{ $posts->first()->published_at->toRssString() }}</lastBuildDate>
@endif
@foreach ($posts as $post)
        <item>
            <title>{{ $post->getTranslation('title', $locale) }}</title>
            <link>{{ $baseUrl }}/{{ $locale }}/blog/{{ $post->slug }}</link>
            <guid isPermaLink="true">{{ $baseUrl }}/{{ $locale }}/blog/{{ $post->slug }}</guid>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
@if ($post->getTranslation('excerpt', $locale))
            <description>{{ $post->getTranslation('excerpt', $locale) }}</description>
@endif
            <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $content->withMentions($post->getTranslation('body', $locale), $locale)['html']) !!}]]></content:encoded>
@foreach ($post->tags as $tag)
            <category>{{ $tag->getTranslation('name', $locale) }}</category>
@endforeach
        </item>
@endforeach
    </channel>
</rss>
