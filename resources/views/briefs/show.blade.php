@extends('layouts.app')
@section('title', $briefTemplate->name . ' — Brief Generator')

@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:680px;">

    {{-- Back --}}
    <a href="{{ route('briefs.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#787583;text-decoration:none;margin-bottom:40px;transition:color 150ms;" onmouseover="this.style.color='#131B2E'" onmouseout="this.style.color='#787583'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Brief Templates
    </a>

    @if($briefTemplate->industry)<span class="badge" style="display:inline-block;margin-bottom:12px;">{{ $briefTemplate->industry }}</span>@endif
    <h1 class="page-title" style="margin:0 0 12px;">{{ $briefTemplate->name }}</h1>
    @if($briefTemplate->description)
    <p style="font-size:15px;color:#454F5E;line-height:1.65;margin:0 0 40px;">{{ $briefTemplate->description }}</p>
    @endif

    <form action="{{ route('briefs.generate', $briefTemplate->slug) }}" method="POST" style="display:flex;flex-direction:column;gap:20px;">
        @csrf
        @foreach($briefTemplate->fields as $field)
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#454F5E;margin-bottom:8px;">{{ $field['label'] }}</label>
            @if(($field['type'] ?? 'text') === 'textarea')
            <textarea name="{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" rows="3" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:14px;color:#131B2E;font-family:inherit;resize:vertical;outline:none;transition:border-color 150ms;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'"></textarea>
            @else
            <input type="text" name="{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;transition:border-color 150ms;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            @endif
        </div>
        @endforeach
        <button type="submit" class="btn-primary" style="margin-top:12px;padding:14px;justify-content:center;font-size:15px;">Generate Brief →</button>
    </form>
</div>
@endsection
