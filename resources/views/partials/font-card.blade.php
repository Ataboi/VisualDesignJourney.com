<a href="{{ route('font-pairings.show', $pair->slug) }}" class="card" style="padding:24px;text-decoration:none;display:block;">
    {{-- Preview --}}
    <div style="margin-bottom:20px;min-height:80px;">
        <p style="font-family:'{{ $pair->heading_font }}',serif;font-weight:{{ $pair->heading_weight }};font-size:1.75rem;line-height:1.1;color:#131B2E;margin:0 0 8px;letter-spacing:0;">{{ $pair->preview_text ?? 'Design with intention.' }}</p>
        <p style="font-family:'{{ $pair->body_font }}',sans-serif;font-weight:{{ $pair->body_weight }};font-size:13px;line-height:1.6;color:#787583;margin:0;">The body text creates natural hierarchy alongside a strong heading.</p>
    </div>
    {{-- Footer --}}
    <div style="display:flex;align-items:center;justify-content:space-between;border-top:.5px solid #E9ECEF;padding-top:16px;">
        <div>
            <p style="font-size:12px;font-weight:600;color:#454F5E;margin:0 0 2px;">{{ $pair->heading_font }}</p>
            <p style="font-size:11px;color:#787583;margin:0;">+ {{ $pair->body_font }}</p>
        </div>
        @if($pair->style)
        <span class="tag-pill">{{ $pair->style }}</span>
        @endif
    </div>
</a>
