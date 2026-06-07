@php $routeName = "color-refs.{$type}.show"; @endphp
<a href="{{ route($routeName, $reference->slug) }}" class="card" style="text-decoration:none;display:block;overflow:hidden;">
    @if($reference->colors)
    <div style="display:flex;height:64px;">
        @foreach($reference->colors as $color)
        <div style="flex:1;background:{{ $color }};"></div>
        @endforeach
    </div>
    @else
    <div style="height:64px;background:#F8F9FA;"></div>
    @endif
    <div style="padding:14px 16px;">
        <p style="font-size:13px;font-weight:600;color:#131B2E;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $reference->name }}</p>
        @if($reference->style)<span class="tag-pill">{{ $reference->style }}</span>@endif
    </div>
</a>
