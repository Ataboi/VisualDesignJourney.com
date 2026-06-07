<a href="{{ route('website-sections.show', $section->slug) }}" class="card" style="text-decoration:none;display:block;overflow:hidden;">
    {{-- Preview --}}
    @if($section->preview_image)
    <div style="aspect-ratio:16/9;overflow:hidden;">
        <img src="{{ Storage::url($section->preview_image) }}" alt="{{ $section->title }}" style="width:100%;height:100%;object-fit:cover;transition:transform 220ms ease-out;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
    </div>
    @else
    <div style="aspect-ratio:16/9;background:#F8F9FA;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
        {{-- Grid pattern --}}
        <svg width="100%" height="100%" style="position:absolute;inset:0;opacity:0.15;" xmlns="http://www.w3.org/2000/svg">
            <defs><pattern id="grid-{{ $section->id }}" width="32" height="32" patternUnits="userSpaceOnUse"><path d="M 32 0 L 0 0 0 32" fill="none" stroke="#E9ECEF" stroke-width="0.5"/></pattern></defs>
            <rect width="100%" height="100%" fill="url(#grid-{{ $section->id }})"/>
        </svg>
        <span style="font-size:24px;color:#E9ECEF;position:relative;">▦</span>
    </div>
    @endif
    {{-- Info --}}
    <div style="padding:16px 18px;">
        <h3 style="font-size:14px;font-weight:700;color:#131B2E;margin:0 0 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $section->title }}</h3>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <span class="tag-pill">{{ $section->section_type }}</span>
            @if($section->style)<span class="tag-pill">{{ $section->style }}</span>@endif
        </div>
    </div>
</a>
