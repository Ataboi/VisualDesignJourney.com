@extends('layouts.app')

@section('title', $post->localizedSeoTitle($locale))
@section('meta_description', $description)
@section('og_title', $post->localizedSeoTitle($locale))
@section('og_description', $description)
@section('canonical', $canonical)

@push('head')
    @foreach($alternates as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endpush

@push('og')
    @if($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
@endpush

@section('content')
    <article class="vk-container" style="max-width:980px;padding-top:48px;">
        <header style="margin-bottom:28px;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
                @foreach($post->categories as $category)
                    <span class="badge">{{ $category->name }}</span>
                @endforeach
                <span class="badge badge-acid">{{ strtoupper($locale) }}</span>
            </div>
            <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,78px);line-height:.98;font-weight:900;">{{ $post->localizedTitle($locale) }}</h1>
            <p style="margin:20px 0 0;color:#454F5E;font-size:14px;">{{ optional($post->published_at)->format('F j, Y') }} · {{ $post->author ?: 'Visual Design Journey' }}</p>
        </header>

        @if($image)
            <img src="{{ $image }}" alt="{{ $post->localizedTitle($locale) }}" style="width:100%;max-height:540px;object-fit:cover;border-radius:8px;border:.5px solid #E9ECEF;margin-bottom:30px;">
        @endif

        <div class="vdj-prose">
            {!! $post->localizedContent($locale) !!}
        </div>
    </article>
@endsection
