@extends('layouts.app')
@section('title', 'Brief Generator')
@section('meta_description', 'Generate landing page design briefs instantly. Choose a template and fill in your project details.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:64px;">

    {{-- Header --}}
    <div style="margin-bottom:48px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Briefs</p>
        <h1 class="page-title" style="margin:0 0 12px;">Brief Generator</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">Pick a template, fill in your details, and get a clean project brief to kick off your design work.</p>
    </div>

    @if($templates->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">⊡</div>
        <p class="empty-state-title">No templates yet.</p>
        <p class="empty-state-desc">Check back soon — templates are coming.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($templates as $template)
        <a href="{{ route('briefs.show', $template->slug) }}" class="card" style="padding:24px;text-decoration:none;display:block;">
            <div style="width:40px;height:40px;border-radius:10px;background:#F2F3FF;border:.5px solid #E9ECEF;display:flex;align-items:center;justify-content:center;font-size:18px;color:#454F5E;margin-bottom:16px;">⊡</div>
            <h2 style="font-size:15px;font-weight:700;color:#131B2E;margin:0 0 8px;line-height:1.3;">{{ $template->name }}</h2>
            @if($template->industry)<span class="badge" style="margin-bottom:10px;display:inline-block;">{{ $template->industry }}</span>@endif
            @if($template->description)
            <p style="font-size:13px;color:#454F5E;margin:0 0 14px;line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $template->description }}</p>
            @endif
            <p style="font-size:11px;color:#787583;margin:0;">Used {{ $template->use_count }} {{ Str::plural('time', $template->use_count) }}</p>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
