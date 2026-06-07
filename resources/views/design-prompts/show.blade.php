@extends('layouts.app')
@section('title', $designPrompt->title . ' — Design Prompt')
@section('meta_description', 'Design prompt: ' . $designPrompt->title . '. Type: ' . $designPrompt->type . ', difficulty: ' . $designPrompt->difficulty . '.')

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">

    {{-- Back --}}
    <a href="{{ route('design-prompts.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Design Prompts
    </a>

    {{-- Badges --}}
    @php
    $diffColor = match($designPrompt->difficulty) { 'easy' => '#6EE7B7', 'hard' => '#FF4D4D', default => '#FFB020' };
    $diffBg = match($designPrompt->difficulty) { 'easy' => 'rgba(110,231,183,0.1)', 'hard' => 'rgba(255,77,77,0.1)', default => 'rgba(255,176,32,0.1)' };
    @endphp
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
        <span class="badge badge-purple">{{ $designPrompt->type }}</span>
        <span class="badge" style="background:{{ $diffBg }};border-color:{{ $diffColor }}33;color:{{ $diffColor }};">{{ $designPrompt->difficulty }}</span>
        @if($designPrompt->tool)<span class="badge">{{ $designPrompt->tool }}</span>@endif
    </div>

    <h1 class="page-title" style="margin:0 0 32px;">{{ $designPrompt->title }}</h1>

    {{-- Prompt block --}}
    <div class="code-block" style="margin-bottom:24px;">
        <div class="code-block-header">
            <span class="code-block-label">The Prompt</span>
            <button onclick="copyPrompt(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;transition:color 150ms,border-color 150ms;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
        </div>
        <div id="prompt-text" style="padding:24px;font-size:15px;color:#131B2E;line-height:1.75;font-family:var(--font-mono);white-space:pre-wrap;word-break:break-word;">{{ $designPrompt->prompt }}</div>
    </div>

    {{-- Main copy CTA --}}
    <button onclick="copyPrompt(null)" class="btn-primary" style="margin-bottom:40px;">Copy Prompt →</button>

    {{-- Notes --}}
    @if($designPrompt->notes)
    <div style="border:.5px solid #E9ECEF;border-left:3px solid rgba(127,119,221,0.6);border-radius:0 12px 12px 0;padding:20px 24px;margin-bottom:32px;background:#FFFFFF;">
        <p style="font-size:11px;font-weight:600;color:#7F77DD;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Notes</p>
        <p style="font-size:14px;color:#454F5E;line-height:1.65;margin:0;">{{ $designPrompt->notes }}</p>
    </div>
    @endif

    {{-- Tags --}}
    @if($designPrompt->tags->isNotEmpty())
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:64px;">
        @foreach($designPrompt->tags as $tag)<span class="tag-pill">#{{ $tag->name }}</span>@endforeach
    </div>
    @endif

    {{-- Related --}}
    @if($related->isNotEmpty())
    <h2 class="section-title" style="margin-bottom:24px;">More {{ ucfirst($designPrompt->type) }} Prompts</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach($related as $prompt)
            @include('partials.prompt-card', ['prompt' => $prompt])
        @endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyPrompt(btn) {
    navigator.clipboard.writeText(document.getElementById('prompt-text').textContent.trim());
    if (btn) {
        btn.textContent = 'Copied!'; btn.style.color = '#7F77DD'; btn.style.borderColor = 'rgba(127,119,221,0.3)'; btn.dataset.copied = '1';
        setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = ''; btn.style.borderColor = '#E9ECEF'; delete btn.dataset.copied; }, 2000);
    }
}
</script>
@endpush
@endsection
