<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>{{ config('app.name', 'Visual Design Journey') }}</title>
        <link>{{ url('/') }}</link>
        <description>Latest design articles from Visual Design Journey.</description>
        <language>en</language>
        @foreach($posts as $post)
            <item>
                <title><![CDATA[{{ $post->localizedTitle('en') }}]]></title>
                <link>{{ \App\Support\Vdj::blogUrl($post, 'en') }}</link>
                <guid isPermaLink="true">{{ \App\Support\Vdj::blogUrl($post, 'en') }}</guid>
                <pubDate>{{ optional($post->published_at)->toRfc2822String() }}</pubDate>
                <description><![CDATA[{{ \App\Support\Vdj::plainText($post->localizedExcerpt('en') ?: $post->localizedContent('en'), 240) }}]]></description>
            </item>
        @endforeach
    </channel>
</rss>
