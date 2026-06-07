@extends('layouts.app')
@section('title', $websiteSection->title . ' — Section Inspiration')
@section('meta_description', 'Website section: ' . $websiteSection->title . '. Type: ' . $websiteSection->section_type . ($websiteSection->style ? ', style: ' . $websiteSection->style : '') . '.')

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    {{-- Back --}}
    <a href="{{ route('website-sections.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Section Gallery
    </a>

    {{-- Badges + Title --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        <span class="tag-pill">{{ $websiteSection->section_type }}</span>
        @if($websiteSection->style)<span class="tag-pill">{{ $websiteSection->style }}</span>@endif
    </div>

    <h1 class="page-title" style="margin:0 0 16px;">{{ $websiteSection->title }}</h1>

    @if($websiteSection->description)
    <p style="font-size:15px;color:#454F5E;line-height:1.65;max-width:640px;margin:0 0 32px;">{{ $websiteSection->description }}</p>
    @endif

    {{-- Preview --}}
    @if($websiteSection->preview_image)
    <div style="border:.5px solid #E9ECEF;border-radius:14px;overflow:hidden;margin-bottom:32px;">
        <img src="{{ Storage::url($websiteSection->preview_image) }}" alt="{{ $websiteSection->title }}" style="width:100%;display:block;">
    </div>
    @else
    <div style="border:.5px solid #E9ECEF;border-radius:14px;overflow:hidden;margin-bottom:32px;background:#F8F9FA;aspect-ratio:16/7;display:flex;align-items:center;justify-content:center;position:relative;">
        <svg width="100%" height="100%" style="position:absolute;inset:0;opacity:0.15;" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid-show" width="32" height="32" patternUnits="userSpaceOnUse"><path d="M 32 0 L 0 0 0 32" fill="none" stroke="#E9ECEF" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid-show)"/></svg>
        <span style="font-size:40px;color:#E9ECEF;position:relative;">▦</span>
    </div>
    @endif

    {{-- Source link + Tags --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:64px;">
        @if($websiteSection->source_url)
        <a href="{{ $websiteSection->source_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary" style="font-size:13px;padding:8px 16px;">View Source ↗</a>
        @endif
        @if($websiteSection->tags->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
            @foreach($websiteSection->tags as $tag)<span class="tag-pill">#{{ $tag->name }}</span>@endforeach
        </div>
        @endif
    </div>

    {{-- Related --}}
    @if($related->isNotEmpty())
    <h2 class="section-title" style="margin-bottom:24px;">More {{ ucfirst($websiteSection->section_type) }} Sections</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($related as $section)
            @include('partials.section-card', ['section' => $section])
        @endforeach
    </div>
    @endif
</div>
@endsection
