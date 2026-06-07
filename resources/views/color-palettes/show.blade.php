@extends('layouts.app')
@section('title', $colorPalette->name . ' — Color Palette')
@section('meta_description', ($colorPalette->mood ? ucfirst($colorPalette->mood) . ' color palette: ' : 'Color palette: ') . implode(', ', $colorPalette->colors) . '. Copy CSS variables and Tailwind config.')

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    {{-- Back --}}
    <a href="{{ route('color-palettes.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Color Palettes
    </a>

    <div style="margin-bottom:8px;">
        @if($colorPalette->mood)<span class="badge" style="margin-bottom:12px;display:inline-block;">{{ $colorPalette->mood }}</span>@endif
        <h1 class="page-title" style="margin:0 0 12px;">{{ $colorPalette->name }}</h1>
        @if($colorPalette->description)<p style="font-size:15px;color:#454F5E;line-height:1.65;max-width:640px;">{{ $colorPalette->description }}</p>@endif
    </div>

    {{-- Swatch strip — click to copy --}}
    <div style="border-radius:14px;overflow:hidden;display:flex;height:96px;margin:32px 0 8px;cursor:pointer;" id="swatch-strip">
        @foreach($colorPalette->colors as $i => $color)
        <div onclick="copyColor('{{ $color }}', {{ $i }})" title="Click to copy {{ $color }}" style="flex:1;background-color:{{ $color }};position:relative;transition:flex 200ms ease;" onmouseover="this.style.flex='1.4'" onmouseout="this.style.flex='1'">
            <div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);opacity:0;transition:opacity 150ms;background:rgba(0,0,0,0.75);border-radius:4px;padding:2px 7px;font-size:10px;font-family:var(--font-mono);color:#fff;white-space:nowrap;" id="swatch-label-{{ $i }}">{{ $color }}</div>
        </div>
        @endforeach
    </div>

    {{-- Color hex list --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:40px;">
        @foreach($colorPalette->colors as $i => $color)
        <button onclick="copyColor('{{ $color }}', {{ $i }})" id="hex-btn-{{ $i }}" style="display:flex;align-items:center;gap:8px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 12px;cursor:pointer;transition:border-color 150ms;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">
            <div style="width:14px;height:14px;border-radius:3px;background:{{ $color }};flex-shrink:0;"></div>
            <span style="font-size:12px;font-family:var(--font-mono);color:#454F5E;">{{ $color }}</span>
        </button>
        @endforeach
    </div>

    {{-- Code blocks --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" style="margin-bottom:40px;">
        {{-- CSS Variables --}}
        <div class="code-block">
            <div class="code-block-header">
                <span class="code-block-label">CSS Variables</span>
                <button onclick="copyCode(this, 'css-vars')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
            </div>
            <pre id="css-vars" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">:root {
@foreach($colorPalette->colors as $i => $color)  --color-{{ $loop->iteration }}: {{ $color }};
@endforeach}</pre>
        </div>

        {{-- Tailwind --}}
        <div class="code-block">
            <div class="code-block-header">
                <span class="code-block-label">Tailwind Config</span>
                <button onclick="copyCode(this, 'tw-colors')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
            </div>
            <pre id="tw-colors" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">colors: {
@foreach($colorPalette->colors as $i => $color)  '{{ Str::slug($colorPalette->name) }}-{{ $loop->iteration }}': '{{ $color }}',
@endforeach}</pre>
        </div>
    </div>

    {{-- Related --}}
    @if($related->isNotEmpty())
    <div style="margin-top:80px;">
        <h2 class="section-title" style="margin-bottom:24px;">Similar Palettes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($related as $palette)
                @include('partials.palette-card', ['palette' => $palette])
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyColor(hex, idx) {
    navigator.clipboard.writeText(hex);
    const btn = document.getElementById('hex-btn-' + idx);
    if (btn) { const span = btn.querySelector('span'); const orig = span.textContent; span.textContent = 'Copied!'; btn.style.borderColor = 'rgba(127,119,221,0.5)'; setTimeout(() => { span.textContent = orig; btn.style.borderColor = '#E9ECEF'; }, 1500); }
}
function copyCode(btn, id) {
    const el = document.getElementById(id);
    navigator.clipboard.writeText(el.textContent.trim());
    btn.textContent = 'Copied!';
    btn.style.color = '#7F77DD';
    btn.style.borderColor = 'rgba(127,119,221,0.3)';
    btn.dataset.copied = '1';
    setTimeout(() => { btn.textContent = 'Copy'; btn.style.color = ''; btn.style.borderColor = '#E9ECEF'; delete btn.dataset.copied; }, 2000);
}
// show hex label on hover
document.querySelectorAll('#swatch-strip > div').forEach(el => {
    const label = el.querySelector('div');
    el.addEventListener('mouseenter', () => label.style.opacity = '1');
    el.addEventListener('mouseleave', () => label.style.opacity = '0');
});
</script>
@endpush
@endsection
