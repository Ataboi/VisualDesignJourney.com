@extends('layouts.app')
@section('title', $label)
@section('meta_description', 'Browse ' . $label . ' — curated UI patterns with copy-ready HTML and CSS.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">
    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">UI Patterns</p>
        <h1 class="page-title" style="margin:0 0 12px;">{{ $label }}</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Curated patterns ready to preview and copy.</p>
    </div>

    <div style="margin-bottom:24px;">
        <form method="GET" style="display:flex;gap:10px;max-width:480px;">
            <div style="flex:1;display:flex;align-items:center;background-color:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:0 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#787583" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-right:8px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." style="flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#131B2E;padding:10px 0;font-family:inherit;" />
            </div>
            <button type="submit" class="btn-secondary" style="white-space:nowrap;padding:10px 18px;">Search</button>
        </form>
    </div>

    @if($styles->isNotEmpty() || $frameworks->isNotEmpty())
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:48px;align-items:center;">
        @if($styles->isNotEmpty())
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin-right:4px;">Style</span>
        <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['style','page'])) }}" class="filter-pill {{ !request('style') ? 'active' : '' }}">All</a>
        @foreach($styles as $s)
        <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('page'), ['style' => $s])) }}" class="filter-pill {{ request('style') === $s ? 'active' : '' }}">{{ ucfirst($s) }}</a>
        @endforeach
        @endif
        @if($frameworks->isNotEmpty())
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin-left:16px;margin-right:4px;">Framework</span>
        @foreach($frameworks as $f)
        <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('page'), ['framework' => $f])) }}" class="filter-pill {{ request('framework') === $f ? 'active' : '' }}">{{ ucfirst($f) }}</a>
        @endforeach
        @endif
        @if(request()->hasAny(['style','search','framework']))
        <a href="{{ url()->current() }}" style="font-size:12px;color:#FF4D4D;text-decoration:none;margin-left:8px;">Clear filters ×</a>
        @endif
    </div>
    @else
    <div style="margin-bottom:48px;"></div>
    @endif

    @if($patterns->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">◻</div>
        <p class="empty-state-title">No patterns found.</p>
        <p class="empty-state-desc">Try <a href="{{ url()->current() }}" style="color:#574EB1;text-decoration:none;">clearing filters</a>.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($patterns as $pattern)
            @include('partials.pattern-card', ['pattern' => $pattern, 'type' => $type])
        @endforeach
    </div>
    <div style="margin-top:40px;">{{ $patterns->links() }}</div>
    @endif
</div>
@endsection
