@extends('layouts.app')
@section('title', 'Your Brief — ' . $briefTemplate->name)

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:680px;">

    {{-- Back --}}
    <a href="{{ route('briefs.show', $briefTemplate->slug) }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Edit Inputs
    </a>

    <div style="margin-bottom:8px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">{{ $briefTemplate->name }}</p>
        <h1 class="page-title" style="margin:0 0 32px;">Your Brief</h1>
    </div>

    {{-- Output --}}
    <div id="brief-output" style="border:.5px solid #E9ECEF;border-radius:14px;overflow:hidden;margin-bottom:24px;">
        @foreach($briefTemplate->fields as $field)
        @if(!empty($inputs[$field['key']]))
        <div style="padding:20px 24px;border-bottom:.5px solid #E9ECEF;" class="brief-field">
            <p style="font-size:10px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 6px;" class="brief-label">{{ $field['label'] }}</p>
            <p style="font-size:14px;color:#131B2E;line-height:1.65;margin:0;" class="brief-value">{{ $inputs[$field['key']] }}</p>
        </div>
        @endif
        @endforeach
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button onclick="copyBrief(this)" class="btn-primary" style="padding:12px 20px;">Copy Brief</button>
        <a href="{{ route('briefs.show', $briefTemplate->slug) }}" class="btn-secondary" style="padding:12px 20px;text-decoration:none;">Edit Inputs</a>
        <a href="{{ route('briefs.index') }}" class="btn-ghost" style="padding:12px 20px;text-decoration:none;">All Templates</a>
    </div>
</div>

@push('scripts')
<script>
function copyBrief(btn) {
    const fields = document.querySelectorAll('.brief-field');
    const text = [...fields].map(f => {
        const label = f.querySelector('.brief-label')?.textContent?.trim();
        const value = f.querySelector('.brief-value')?.textContent?.trim();
        return label && value ? `${label}\n${value}` : '';
    }).filter(Boolean).join('\n\n');
    navigator.clipboard.writeText(text);
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy Brief', 2000);
}
</script>
@endpush
@endsection
