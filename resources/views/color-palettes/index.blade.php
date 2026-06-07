@extends('layouts.app')
@section('title', 'Color Palettes')
@section('meta_description', 'Curated color palettes for modern web and UI design. Filter by mood: dark, vibrant, pastel, monochrome, earthy, neon.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">

    {{-- Header --}}
    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Colors</p>
        <h1 class="page-title" style="margin:0 0 12px;">Color Palettes</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Ready-to-use color systems for your next design project.</p>
    </div>

    {{-- Search --}}
    <div style="margin-bottom:24px;">
        <form method="GET" style="display:flex;gap:10px;max-width:480px;">
            <div style="flex:1;display:flex;align-items:center;background-color:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:0 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#787583" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-right:8px;">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search palettes..." style="flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#131B2E;padding:10px 0;font-family:inherit;" />
            </div>
            <button type="submit" class="btn-secondary" style="white-space:nowrap;padding:10px 18px;">Search</button>
        </form>
    </div>

    {{-- Mood filters --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:48px;align-items:center;">
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin-right:4px;">Mood</span>
        <a href="{{ route('color-palettes.index', request()->except(['mood','page'])) }}" class="filter-pill {{ !request('mood') ? 'active' : '' }}">All</a>
        @foreach($moods as $mood)
        <a href="{{ route('color-palettes.index', array_merge(request()->except('page'), ['mood' => $mood])) }}" class="filter-pill {{ request('mood') === $mood ? 'active' : '' }}">{{ ucfirst($mood) }}</a>
        @endforeach
        @if(request()->hasAny(['mood','search']))
        <a href="{{ route('color-palettes.index') }}" style="font-size:12px;color:#FF4D4D;text-decoration:none;margin-left:8px;" onmouseover="this.style.color='#FF7A7A'" onmouseout="this.style.color='#FF4D4D'">Clear filters ×</a>
        @endif
    </div>

    {{-- Grid --}}
    @if($palettes->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon" style="font-size:28px;">◉</div>
        <p class="empty-state-title">No palettes found.</p>
        <p class="empty-state-desc">Try another mood or <a href="{{ route('color-palettes.index') }}" style="color:#574EB1;text-decoration:none;">clear filters</a>.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($palettes as $palette)
            @include('partials.palette-card', ['palette' => $palette])
        @endforeach
    </div>
    <div style="margin-top:40px;">{{ $palettes->links() }}</div>
    @endif
</div>
@endsection
