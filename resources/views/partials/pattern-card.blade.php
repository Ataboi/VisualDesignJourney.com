@php $routeName = "patterns.{$type}.show"; @endphp
<a href="{{ route($routeName, $pattern->slug) }}" class="card" style="padding:0;text-decoration:none;display:block;overflow:hidden;">
    @if($pattern->preview_html)
    <div style="height:120px;background:#F8F9FA;overflow:hidden;border-bottom:.5px solid #E9ECEF;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="transform:scale(0.65);transform-origin:center;pointer-events:none;width:100%;">{!! $pattern->preview_html !!}</div>
    </div>
    @else
    <div style="height:88px;background:#F8F9FA;display:flex;align-items:center;justify-content:center;border-bottom:.5px solid #E9ECEF;">
        <span style="font-size:24px;color:#E9ECEF;">◻</span>
    </div>
    @endif
    <div style="padding:14px 16px;">
        <p style="font-size:13px;font-weight:600;color:#131B2E;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $pattern->name }}</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @if($pattern->style)<span class="tag-pill">{{ $pattern->style }}</span>@endif
            @if($pattern->framework)<span class="tag-pill">{{ $pattern->framework }}</span>@endif
        </div>
    </div>
</a>
