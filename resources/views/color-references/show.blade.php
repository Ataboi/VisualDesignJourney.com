@extends('layouts.app')
@section('title', $reference->name . ' — ' . $label)

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    <a href="{{ url()->previous() }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        {{ $label }}
    </a>

    @if($reference->style)<span class="tag-pill" style="display:inline-block;margin-bottom:12px;">{{ $reference->style }}</span>@endif
    <h1 class="page-title" style="margin:0 0 12px;">{{ $reference->name }}</h1>
    @if($reference->description)<p style="font-size:15px;color:#454F5E;line-height:1.65;max-width:640px;margin:0 0 32px;">{{ $reference->description }}</p>@endif

    @if($reference->colors)
    <div style="border-radius:14px;overflow:hidden;display:flex;height:80px;margin-bottom:24px;">
        @foreach($reference->colors as $color)
        <div onclick="navigator.clipboard.writeText('{{ $color }}')" title="Copy {{ $color }}" style="flex:1;background:{{ $color }};cursor:pointer;transition:flex 200ms;" onmouseover="this.style.flex='1.5'" onmouseout="this.style.flex='1'"></div>
        @endforeach
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:32px;">
        @foreach($reference->colors as $color)
        <button onclick="navigator.clipboard.writeText('{{ $color }}')" style="display:flex;align-items:center;gap:8px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:6px 12px;cursor:pointer;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">
            <div style="width:12px;height:12px;border-radius:3px;background:{{ $color }};"></div>
            <span style="font-size:11px;font-family:var(--font-mono);color:#454F5E;">{{ $color }}</span>
        </button>
        @endforeach
    </div>
    @endif

    @if($reference->css_code)
    <div class="code-block" style="margin-bottom:16px;">
        <div class="code-block-header"><span class="code-block-label">CSS</span><button onclick="copyBlock(this,'css-block')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="css-block" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">{{ $reference->css_code }}</pre>
    </div>
    @endif

    @if($reference->tailwind_code)
    <div class="code-block" style="margin-bottom:32px;">
        <div class="code-block-header"><span class="code-block-label">Tailwind</span><button onclick="copyBlock(this,'tw-block')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="tw-block" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">{{ $reference->tailwind_code }}</pre>
    </div>
    @endif

    @if($related->isNotEmpty())
    <h2 class="section-title" style="margin-bottom:24px;">More {{ $label }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($related as $item)
            @include('partials.color-reference-card', ['reference' => $item, 'type' => $type])
        @endforeach
    </div>
    @endif
</div>
@push('scripts')
<script>
function copyBlock(btn, id) {
    navigator.clipboard.writeText(document.getElementById(id).textContent.trim());
    btn.textContent='Copied!';btn.style.color='#7F77DD';btn.style.borderColor='rgba(127,119,221,0.3)';btn.dataset.copied='1';
    setTimeout(()=>{btn.textContent='Copy';btn.style.color='';btn.style.borderColor='#E9ECEF';delete btn.dataset.copied;},2000);
}
</script>
@endpush
@endsection
