@extends('layouts.app')
@section('title', $label)
@section('meta_description', 'Browse ' . $label . ' — visual references with copy-ready CSS and Tailwind snippets.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">
    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Typography & Layout</p>
        <h1 class="page-title" style="margin:0 0 12px;">{{ $label }}</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Visual references with copy-ready CSS and Tailwind snippets.</p>
    </div>

    <div style="margin-bottom:48px;">
        <form method="GET" style="display:flex;gap:10px;max-width:480px;">
            <div style="flex:1;display:flex;align-items:center;background-color:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:0 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#787583" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-right:8px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." style="flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#131B2E;padding:10px 0;font-family:inherit;" />
            </div>
            <button type="submit" class="btn-secondary" style="white-space:nowrap;padding:10px 18px;">Search</button>
        </form>
    </div>

    @if($tools->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">Tт</div>
        <p class="empty-state-title">No entries yet.</p>
        <p class="empty-state-desc">Check back soon.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($tools as $tool)
            @include('partials.type-tool-card', ['tool' => $tool, 'type' => $type])
        @endforeach
    </div>
    <div style="margin-top:40px;">{{ $tools->links() }}</div>
    @endif
</div>
@endsection
