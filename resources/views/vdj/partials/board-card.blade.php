@php
    use App\Support\Vdj;

    $image = Vdj::imageUrl($board->cover_image ?: optional($board->images->first())->image_url);
    $tags = collect(explode(',', (string) $board->style_tags))->map(fn ($tag) => trim($tag))->filter()->take(4);
@endphp

<article class="card">
    <a href="{{ Vdj::boardUrl($board) }}" style="display:block;text-decoration:none;">
        <div style="aspect-ratio:4/3;background:#F8F9FA;overflow:hidden;">
            @if($image)
                <img src="{{ $image }}" alt="{{ $board->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
            @else
                <div style="height:100%;display:grid;place-items:center;color:#787583;font-size:12px;">VDJ</div>
            @endif
        </div>
        <div style="padding:16px;">
            <h3 style="margin:0;color:#131B2E;font-size:16px;line-height:1.4;font-weight:600;">{{ $board->title }}</h3>
            <p style="margin:7px 0 0;color:#454F5E;font-size:12px;">by {{ optional($board->user)->username ?? 'VDJ curator' }}</p>
            @if($tags->count())
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
                    @foreach($tags as $tag)
                        <span class="tag-pill">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            <div style="display:flex;gap:12px;margin-top:14px;color:#787583;font-size:12px;">
                <span>{{ $board->likes_count ?? 0 }} likes</span>
                <span>{{ $board->saves_count ?? $board->save_count ?? 0 }} saves</span>
            </div>
        </div>
    </a>
</article>
