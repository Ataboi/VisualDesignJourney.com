@php
    use App\Support\Vdj;

    $locale = $locale ?? 'en';
    $image = Vdj::imageUrl($post->featured_image);
@endphp

<article class="card">
    <a href="{{ Vdj::blogUrl($post, $locale) }}" style="display:block;text-decoration:none;">
        <div style="aspect-ratio:16/10;background:#F8F9FA;overflow:hidden;">
            @if($image)
                <img src="{{ $image }}" alt="{{ $post->localizedTitle($locale) }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
            @else
                <div style="height:100%;display:grid;place-items:center;color:#787583;font-size:12px;">Article</div>
            @endif
        </div>
        <div style="padding:16px;">
            <p style="margin:0 0 9px;color:#454F5E;font-size:11px;text-transform:uppercase;letter-spacing:.08em;">{{ optional($post->published_at)->format('M j, Y') }}</p>
            <h3 style="margin:0;color:#131B2E;font-size:17px;line-height:1.42;font-weight:600;">{{ $post->localizedTitle($locale) }}</h3>
            <p style="margin:10px 0 0;color:#454F5E;font-size:13px;line-height:1.6;">{{ Vdj::plainText($post->localizedExcerpt($locale) ?: $post->localizedContent($locale), 120) }}</p>
        </div>
    </a>
</article>
