@php $routeName = "type-tools.{$type}.show"; @endphp
<a href="{{ route($routeName, $tool->slug) }}" class="card" style="padding:20px;text-decoration:none;display:block;">
    <p style="font-size:13px;font-weight:600;color:#131B2E;margin:0 0 8px;">{{ $tool->name }}</p>
    @if($tool->base_value || $tool->ratio)
    <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
        @if($tool->base_value)<span class="badge">Base: {{ $tool->base_value }}</span>@endif
        @if($tool->ratio)<span class="badge">Ratio: {{ $tool->ratio }}</span>@endif
    </div>
    @endif
    @if($tool->description)<p style="font-size:12px;color:#787583;margin:0;line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $tool->description }}</p>@endif
    @if($tool->values)
    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:4px;">
        @foreach(array_slice(array_keys($tool->values), 0, 6) as $k)
        <span style="font-size:10px;font-family:var(--font-mono);color:#7F77DD;background:rgba(127,119,221,0.08);border:1px solid rgba(127,119,221,0.15);border-radius:4px;padding:2px 6px;">{{ $k }}</span>
        @endforeach
        @if(count($tool->values) > 6)<span style="font-size:10px;color:#787583;">+{{ count($tool->values) - 6 }} more</span>@endif
    </div>
    @endif
</a>
