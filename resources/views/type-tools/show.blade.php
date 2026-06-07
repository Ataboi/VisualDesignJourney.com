@extends('layouts.app')
@section('title', $tool->name . ' — ' . $label)

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;">

    <a href="{{ url()->previous() }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        {{ $label }}
    </a>

    <h1 class="page-title" style="margin:0 0 12px;">{{ $tool->name }}</h1>
    @if($tool->description)<p style="font-size:15px;color:#454F5E;line-height:1.65;max-width:640px;margin:0 0 12px;">{{ $tool->description }}</p>@endif
    @if($tool->use_case)<p style="font-size:13px;color:#787583;margin:0 0 40px;font-style:italic;">Use when: {{ $tool->use_case }}</p>@endif

    {{-- Values reference table --}}
    @if($tool->values)
    <div style="border:.5px solid #E9ECEF;border-radius:14px;overflow:hidden;margin-bottom:24px;">
        <div style="padding:12px 20px;background:#F8F9FA;border-bottom:.5px solid #E9ECEF;">
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0;">Scale Values</p>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                @foreach($tool->values as $key => $val)
                <tr style="border-bottom:.5px solid #E9ECEF;">
                    <td style="padding:10px 20px;font-family:var(--font-mono);font-size:12px;color:#787583;white-space:nowrap;width:120px;">{{ $key }}</td>
                    <td style="padding:10px 20px;font-family:var(--font-mono);font-size:12px;color:#7F77DD;">{{ is_array($val) ? implode(', ', $val) : $val }}</td>
                    <td style="padding:10px 20px;text-align:right;">
                        <button onclick="navigator.clipboard.writeText('{{ is_array($val) ? implode(', ', $val) : $val }}')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:5px;padding:2px 8px;cursor:pointer;" onmouseover="this.style.color='#7F77DD'" onmouseout="this.style.color='#787583'">Copy</button>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    @endif

    @if($tool->css_snippet)
    <div class="code-block" style="margin-bottom:16px;">
        <div class="code-block-header"><span class="code-block-label">CSS</span><button onclick="copyBlock(this,'css-block')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="css-block" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">{{ $tool->css_snippet }}</pre>
    </div>
    @endif

    @if($tool->tailwind_snippet)
    <div class="code-block" style="margin-bottom:32px;">
        <div class="code-block-header"><span class="code-block-label">Tailwind</span><button onclick="copyBlock(this,'tw-block')" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="tw-block" style="padding:20px 24px;margin:0;font-size:12px;overflow-x:auto;">{{ $tool->tailwind_snippet }}</pre>
    </div>
    @endif

    @if($related->isNotEmpty())
    <h2 class="section-title" style="margin-bottom:24px;">More {{ $label }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($related as $item)
            @include('partials.type-tool-card', ['tool' => $item, 'type' => $type])
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
