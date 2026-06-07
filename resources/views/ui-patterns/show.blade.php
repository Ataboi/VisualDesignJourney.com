@extends('layouts.app')
@section('title', $pattern->name . ' — ' . $label)
@section('meta_description', $pattern->description ?? $pattern->name . ' — ' . $label . ' with copy-ready code.')

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    <a href="{{ url()->previous() }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        {{ $label }}
    </a>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        @if($pattern->style)<span class="tag-pill">{{ $pattern->style }}</span>@endif
        @if($pattern->framework)<span class="badge">{{ $pattern->framework }}</span>@endif
    </div>

    <h1 class="page-title" style="margin:0 0 12px;">{{ $pattern->name }}</h1>
    @if($pattern->description)
    <p style="font-size:15px;color:#454F5E;line-height:1.65;max-width:640px;margin:0 0 40px;">{{ $pattern->description }}</p>
    @endif

    {{-- Live HTML preview --}}
    @if($pattern->preview_html)
    <div style="border:.5px solid #E9ECEF;border-radius:14px;overflow:hidden;margin-bottom:24px;">
        <div style="padding:6px 16px;border-bottom:.5px solid #E9ECEF;background:#F8F9FA;display:flex;align-items:center;gap:8px;">
            <div style="width:10px;height:10px;border-radius:50%;background:#FF4D4D;opacity:0.6;"></div>
            <div style="width:10px;height:10px;border-radius:50%;background:#FFB020;opacity:0.6;"></div>
            <div style="width:10px;height:10px;border-radius:50%;background:#6EE7B7;opacity:0.6;"></div>
            <span style="font-size:11px;color:#787583;margin-left:8px;">Preview</span>
        </div>
        <div style="padding:40px;background:#F8F9FA;overflow:auto;">
            {!! $pattern->preview_html !!}
        </div>
    </div>
    @endif

    {{-- Code block --}}
    @if($pattern->code)
    <div class="code-block" style="margin-bottom:32px;">
        <div class="code-block-header">
            <span class="code-block-label">{{ strtoupper($pattern->language) }}</span>
            <button onclick="copyCode(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
        </div>
        <pre id="pattern-code" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;white-space:pre;">{{ $pattern->code }}</pre>
    </div>
    @endif

    @if($related->isNotEmpty())
    <h2 class="section-title" style="margin-bottom:24px;">More {{ $label }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($related as $item)
            @include('partials.pattern-card', ['pattern' => $item, 'type' => $type])
        @endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyCode(btn) {
    navigator.clipboard.writeText(document.getElementById('pattern-code').textContent.trim());
    btn.textContent = 'Copied!'; btn.style.color = '#7F77DD'; btn.style.borderColor = 'rgba(127,119,221,0.3)'; btn.dataset.copied = '1';
    setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = ''; btn.style.borderColor = '#E9ECEF'; delete btn.dataset.copied; }, 2000);
}
</script>
@endpush
@endsection
