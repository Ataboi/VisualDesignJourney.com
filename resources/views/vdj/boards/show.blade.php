@extends('layouts.app')

@php
    use App\Support\Vdj;

    $cover = Vdj::imageUrl($board->cover_image ?: optional($board->images->first())->image_url);
    $tags = collect(explode(',', (string) $board->style_tags))->map(fn ($tag) => trim($tag))->filter();
@endphp

@section('title', $board->title)
@section('meta_description', Vdj::plainText($board->description, 160))
@section('canonical', $canonical)

@section('content')
    <section class="vk-container" style="padding-top:44px;">
        <div class="vdj-board-detail-grid" style="gap:24px;align-items:start;">
            <div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                    @foreach($tags->take(8) as $tag)
                        <span class="tag-pill">{{ $tag }}</span>
                    @endforeach
                </div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,74px);line-height:.98;font-weight:900;">{{ $board->title }}</h1>
                @if($board->description)
                    <p style="max-width:720px;margin:18px 0 0;color:#454F5E;font-size:16px;line-height:1.75;">{{ $board->description }}</p>
                @endif
            </div>
            <aside class="card-lg" style="padding:18px;border-radius:8px;">
                <p style="margin:0;color:#454F5E;font-size:12px;text-transform:uppercase;letter-spacing:.08em;">Curated by</p>
                <a href="{{ optional($board->user)->username ? route('profile.show', ['username' => $board->user->username]) : '#' }}" style="display:block;margin-top:8px;color:#131B2E;text-decoration:none;font-weight:750;">{{ optional($board->user)->username ?? 'VDJ curator' }}</a>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:18px;">
                    <div><b style="display:block;color:#131B2E;">{{ $board->likes_count ?? 0 }}</b><span style="color:#454F5E;font-size:12px;">likes</span></div>
                    <div><b style="display:block;color:#131B2E;">{{ $board->saves_count ?? $board->save_count ?? 0 }}</b><span style="color:#454F5E;font-size:12px;">saves</span></div>
                    <div><b style="display:block;color:#131B2E;">{{ $board->view_count ?? 0 }}</b><span style="color:#454F5E;font-size:12px;">views</span></div>
                </div>
            </aside>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-top:32px;">
            @forelse($board->images as $image)
                @php($url = Vdj::imageUrl($image->image_url))
                <figure class="card" style="border-radius:8px;margin:0;">
                    @if($url)
                        <img src="{{ $url }}" alt="{{ $image->caption ?: $board->title }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
                    @endif
                    @if($image->caption || $image->credit)
                        <figcaption style="padding:12px;color:#454F5E;font-size:12px;line-height:1.5;">{{ $image->caption ?: $image->credit }}</figcaption>
                    @endif
                </figure>
            @empty
                @if($cover)
                    <img src="{{ $cover }}" alt="{{ $board->title }}" style="width:100%;border-radius:8px;border:.5px solid #E9ECEF;">
                @endif
            @endforelse
        </div>
    </section>
@endsection
