@extends('layouts.app')
@section('title', 'Website Section Gallery')
@section('meta_description', 'Browse website section inspiration: heroes, navbars, features, pricing and more. Filter by type and style.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">

    {{-- Header --}}
    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Sections</p>
        <h1 class="page-title" style="margin:0 0 12px;">Section Gallery</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Website section inspiration — heroes, navbars, pricing, CTAs, and more.</p>
    </div>

    {{-- Search --}}
    <div style="margin-bottom:24px;">
        <form method="GET" style="display:flex;gap:10px;max-width:480px;">
            <div style="flex:1;display:flex;align-items:center;background-color:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:0 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#787583" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-right:8px;">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search sections..." style="flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#131B2E;padding:10px 0;font-family:inherit;" />
            </div>
            <button type="submit" class="btn-secondary" style="white-space:nowrap;padding:10px 18px;">Search</button>
        </form>
    </div>

    {{-- Type filters --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;align-items:center;">
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;width:40px;flex-shrink:0;">Type</span>
        <a href="{{ route('website-sections.index', request()->except(['section_type','page'])) }}" class="filter-pill {{ !request('section_type') ? 'active' : '' }}">All</a>
        @foreach($types as $type)
        <a href="{{ route('website-sections.index', array_merge(request()->except('page'), ['section_type' => $type])) }}" class="filter-pill {{ request('section_type') === $type ? 'active' : '' }}">{{ ucfirst($type) }}</a>
        @endforeach
    </div>

    {{-- Style filters --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:48px;align-items:center;">
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;width:40px;flex-shrink:0;">Style</span>
        <a href="{{ route('website-sections.index', request()->except(['style','page'])) }}" class="filter-pill {{ !request('style') ? 'active' : '' }}">All</a>
        @foreach($styles as $style)
        <a href="{{ route('website-sections.index', array_merge(request()->except('page'), ['style' => $style])) }}" class="filter-pill {{ request('style') === $style ? 'active' : '' }}">{{ ucfirst($style) }}</a>
        @endforeach
        @if(request()->hasAny(['section_type','style','search']))
        <a href="{{ route('website-sections.index') }}" style="font-size:12px;color:#FF4D4D;text-decoration:none;margin-left:8px;" onmouseover="this.style.color='#FF7A7A'" onmouseout="this.style.color='#FF4D4D'">Clear filters ×</a>
        @endif
    </div>

    {{-- Grid --}}
    @if($sections->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">▦</div>
        <p class="empty-state-title">No sections found.</p>
        <p class="empty-state-desc">Try another type or <a href="{{ route('website-sections.index') }}" style="color:#574EB1;text-decoration:none;">clear filters</a>.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($sections as $section)
            @include('partials.section-card', ['section' => $section])
        @endforeach
    </div>
    <div style="margin-top:40px;">{{ $sections->links() }}</div>
    @endif
</div>
@endsection
