@php
$routeName = "snippets.{$type}.show";
@endphp
<a href="{{ route($routeName, $snippet->slug) }}" class="card" style="padding:0;text-decoration:none;display:block;overflow:hidden;">
    {{-- Preview --}}
    @if($snippet->preview_css || $snippet->preview_html)
    <div style="height:88px;background:#F8F9FA;display:flex;align-items:center;justify-content:center;padding:16px;border-bottom:.5px solid #E9ECEF;overflow:hidden;">
        @if($snippet->preview_html)
            <div style="transform:scale(0.7);transform-origin:center;pointer-events:none;">{!! $snippet->preview_html !!}</div>
        @else
            <div style="{{ $snippet->preview_css }}width:80%;height:48px;border-radius:8px;"></div>
        @endif
    </div>
    @else
    <div style="height:88px;background:#F8F9FA;display:flex;align-items:center;justify-content:center;border-bottom:.5px solid #E9ECEF;">
        <span style="font-size:22px;color:#E9ECEF;font-family:var(--font-mono);">{ }</span>
    </div>
    @endif
    {{-- Info --}}
    <div style="padding:14px 16px;">
        <p style="font-size:13px;font-weight:600;color:#131B2E;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $snippet->name }}</p>
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            @if($snippet->style)<span class="tag-pill">{{ $snippet->style }}</span>@endif
            <span style="font-size:11px;color:#E9ECEF;">{{ strtoupper($snippet->language) }}</span>
        </div>
    </div>
</a>
