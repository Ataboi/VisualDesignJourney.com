@extends('layouts.app')
@section('title', $label)
@section('meta_description', 'Browse ' . $label . ' — copy-ready CSS snippets for modern designers and developers.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">

    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">CSS Tools</p>
        <h1 class="page-title" style="margin:0 0 12px;">{{ $label }}</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Copy-ready snippets. Click any card to grab the code.</p>
    </div>

    {{-- Search + filters --}}
    <div style="margin-bottom:24px;">
        <form method="GET" style="display:flex;gap:10px;max-width:480px;">
            <div style="flex:1;display:flex;align-items:center;background-color:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:0 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#787583" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-right:8px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search {{ strtolower($label) }}..." style="flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#131B2E;padding:10px 0;font-family:inherit;" />
            </div>
            <button type="submit" class="btn-secondary" style="white-space:nowrap;padding:10px 18px;">Search</button>
        </form>
    </div>

    @if($styles->isNotEmpty())
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:48px;align-items:center;">
        <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin-right:4px;">Style</span>
        <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['style','page'])) }}" class="filter-pill {{ !request('style') ? 'active' : '' }}">All</a>
        @foreach($styles as $s)
        <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->except('page'), ['style' => $s])) }}" class="filter-pill {{ request('style') === $s ? 'active' : '' }}">{{ ucfirst($s) }}</a>
        @endforeach
        @if(request()->hasAny(['style','search']))
        <a href="{{ url()->current() }}" style="font-size:12px;color:#FF4D4D;text-decoration:none;margin-left:8px;">Clear filters ×</a>
        @endif
    </div>
    @else
    <div style="margin-bottom:48px;"></div>
    @endif

    @if($snippets->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">{ }</div>
        <p class="empty-state-title">No snippets found.</p>
        <p class="empty-state-desc">Try clearing filters or <a href="{{ url()->current() }}" style="color:#574EB1;text-decoration:none;">reset search</a>.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($snippets as $snippet)
            @include('partials.snippet-card', ['snippet' => $snippet, 'type' => $snippetType])
        @endforeach
    </div>
    <div style="margin-top:40px;">{{ $snippets->links() }}</div>
    @endif
</div>
@endsection
