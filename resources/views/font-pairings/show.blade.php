@extends('layouts.app')
@section('title', $fontPairing->name . ' — Font Pairing')
@section('meta_description', 'Font pairing: ' . $fontPairing->heading_font . ' + ' . $fontPairing->body_font . '. ' . ($fontPairing->description ?? 'A curated heading and body font combination for modern design.'))

@push('fonts')
<link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontPairing->heading_font) }}:wght@{{ $fontPairing->heading_weight }}&family={{ urlencode($fontPairing->body_font) }}:wght@300;400;{{ $fontPairing->body_weight }}&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    {{-- Back --}}
    <a href="{{ route('font-pairings.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Font Pairings
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Main preview --}}
        <div style="grid-column:span 2;">
            {{-- Large preview card --}}
            <div class="card-lg" style="padding:48px;margin-bottom:24px;border:.5px solid #E9ECEF;">
                <p style="font-family:'{{ $fontPairing->heading_font }}',serif;font-weight:{{ $fontPairing->heading_weight }};font-size:3rem;line-height:1.05;color:#131B2E;margin:0 0 20px;letter-spacing:0;">{{ $fontPairing->preview_text ?? 'Design with clear intention.' }}</p>
                <p style="font-family:'{{ $fontPairing->body_font }}',sans-serif;font-weight:{{ $fontPairing->body_weight }};font-size:16px;line-height:1.7;color:#454F5E;margin:0;">The body text flows naturally alongside a strong heading to create clear visual hierarchy. Good typography communicates before the reader even begins to read the words. Use spacing and weight to guide the eye.</p>
            </div>

            {{-- Specimen sizes --}}
            <div class="card-lg" style="padding:32px 40px;margin-bottom:24px;border:.5px solid #E9ECEF;">
                <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 24px;">Type Specimen</p>
                <div style="display:flex;flex-direction:column;gap:16px;border-bottom:.5px solid #E9ECEF;padding-bottom:24px;margin-bottom:24px;">
                    @foreach([['3rem','Heading XL'],['2rem','Heading LG'],['1.5rem','Heading MD'],['1.125rem','Heading SM']] as [$size,$label])
                    <div style="display:flex;align-items:baseline;gap:16px;">
                        <span style="font-size:10px;color:#E9ECEF;width:72px;flex-shrink:0;">{{ $label }}</span>
                        <p style="font-family:'{{ $fontPairing->heading_font }}',serif;font-weight:{{ $fontPairing->heading_weight }};font-size:{{ $size }};line-height:1.1;color:#131B2E;margin:0;">{{ $fontPairing->heading_font }}</p>
                    </div>
                    @endforeach
                </div>
                <p style="font-family:'{{ $fontPairing->body_font }}',sans-serif;font-size:14px;line-height:1.75;color:#454F5E;margin:0;">{{ $fontPairing->body_font }} — The quick brown fox jumps over the lazy dog. Pack my box with five dozen liquor jugs. How vexingly quick daft zebras jump!</p>
            </div>

            {{-- CSS Import --}}
            <div class="code-block" style="margin-bottom:24px;">
                <div class="code-block-header">
                    <span class="code-block-label">CSS @import</span>
                    <button onclick="copyCode(this, 'css-import')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;transition:color 150ms,border-color 150ms;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
                </div>
                <pre id="css-import" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">@import url('https://fonts.googleapis.com/css2?family={{ urlencode($fontPairing->heading_font) }}:wght@{{ $fontPairing->heading_weight }}&family={{ urlencode($fontPairing->body_font) }}:wght@{{ $fontPairing->body_weight }}&display=swap');

h1, h2, h3, h4 {
  font-family: '{{ $fontPairing->heading_font }}', serif;
  font-weight: {{ $fontPairing->heading_weight }};
}

body, p, span {
  font-family: '{{ $fontPairing->body_font }}', sans-serif;
  font-weight: {{ $fontPairing->body_weight }};
}</pre>
            </div>

            {{-- Tailwind --}}
            <div class="code-block">
                <div class="code-block-header">
                    <span class="code-block-label">Tailwind Config</span>
                    <button onclick="copyCode(this, 'tw-config')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
                </div>
                <pre id="tw-config" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">// tailwind.config.js
theme: {
  extend: {
    fontFamily: {
      heading: ['{{ $fontPairing->heading_font }}', 'serif'],
      body:    ['{{ $fontPairing->body_font }}', 'sans-serif'],
    }
  }
}</pre>
            </div>
        </div>

        {{-- Sidebar metadata --}}
        <div>
            <div style="border:.5px solid #E9ECEF;border-radius:14px;padding:24px;margin-bottom:16px;">
                <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 20px;">Pairing Details</p>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div>
                        <p style="font-size:11px;color:#787583;margin:0 0 4px;text-transform:uppercase;letter-spacing:0.06em;">Heading</p>
                        <p style="font-size:14px;font-weight:600;color:#131B2E;margin:0;">{{ $fontPairing->heading_font }}</p>
                        <p style="font-size:12px;color:#454F5E;margin:2px 0 0;">Weight {{ $fontPairing->heading_weight }} · {{ $fontPairing->heading_size }}</p>
                    </div>
                    <div style="border-top:.5px solid #E9ECEF;padding-top:16px;">
                        <p style="font-size:11px;color:#787583;margin:0 0 4px;text-transform:uppercase;letter-spacing:0.06em;">Body</p>
                        <p style="font-size:14px;font-weight:600;color:#131B2E;margin:0;">{{ $fontPairing->body_font }}</p>
                        <p style="font-size:12px;color:#454F5E;margin:2px 0 0;">Weight {{ $fontPairing->body_weight }} · {{ $fontPairing->body_size }}</p>
                    </div>
                    @if($fontPairing->style)
                    <div style="border-top:.5px solid #E9ECEF;padding-top:16px;">
                        <p style="font-size:11px;color:#787583;margin:0 0 8px;text-transform:uppercase;letter-spacing:0.06em;">Style</p>
                        <span class="tag-pill">{{ $fontPairing->style }}</span>
                    </div>
                    @endif
                    @if($fontPairing->tags->isNotEmpty())
                    <div style="border-top:.5px solid #E9ECEF;padding-top:16px;">
                        <p style="font-size:11px;color:#787583;margin:0 0 8px;text-transform:uppercase;letter-spacing:0.06em;">Tags</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($fontPairing->tags as $tag)<span class="tag-pill">{{ $tag->name }}</span>@endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @if($fontPairing->description)
            <div style="border:.5px solid #E9ECEF;border-radius:14px;padding:20px;">
                <p style="font-size:13px;color:#454F5E;line-height:1.65;margin:0;">{{ $fontPairing->description }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Related --}}
    @if($related->isNotEmpty())
    <div style="margin-top:80px;">
        <h2 class="section-title" style="margin-bottom:24px;">Related Pairings</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($related as $pair)
                @include('partials.font-card', ['pair' => $pair])
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyCode(btn, id) {
    const el = document.getElementById(id);
    navigator.clipboard.writeText(el.textContent.trim());
    btn.textContent = 'Copied!';
    btn.style.color = '#7F77DD';
    btn.style.borderColor = 'rgba(127,119,221,0.3)';
    btn.dataset.copied = '1';
    setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = ''; btn.style.borderColor = '#E9ECEF'; delete btn.dataset.copied; }, 2000);
}
</script>
@endpush
@endsection
