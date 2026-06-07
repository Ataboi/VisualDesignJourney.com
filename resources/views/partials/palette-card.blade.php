<a href="{{ route('color-palettes.show', $palette->slug) }}" class="card" style="text-decoration:none;display:block;overflow:hidden;">
    {{-- Swatch strip --}}
    <div style="display:flex;height:72px;">
        @foreach($palette->colors as $color)
        <div style="flex:1;background-color:{{ $color }};"></div>
        @endforeach
    </div>
    {{-- Info --}}
    <div style="padding:14px 16px;">
        <p style="font-size:13px;font-weight:600;color:#131B2E;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $palette->name }}</p>
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            @if($palette->mood)<span class="tag-pill">{{ $palette->mood }}</span>@endif
            <span style="font-size:11px;color:#E9ECEF;">{{ count($palette->colors) }} colors</span>
        </div>
    </div>
</a>
