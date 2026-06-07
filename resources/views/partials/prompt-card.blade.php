@php
$difficultyColor = match($prompt->difficulty) {
    'easy'  => '#6EE7B7',
    'hard'  => '#FF4D4D',
    default => '#FFB020',
};
$difficultyBg = match($prompt->difficulty) {
    'easy'  => 'rgba(110,231,183,0.1)',
    'hard'  => 'rgba(255,77,77,0.1)',
    default => 'rgba(255,176,32,0.1)',
};
@endphp
<a href="{{ route('design-prompts.show', $prompt->slug) }}" class="card" style="padding:20px;text-decoration:none;display:block;">
    {{-- Tags row --}}
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
        <span class="badge badge-purple">{{ $prompt->type }}</span>
        <span class="badge" style="background-color:{{ $difficultyBg }};border-color:{{ $difficultyColor }}33;color:{{ $difficultyColor }};">{{ $prompt->difficulty }}</span>
        @if($prompt->tool)
        <span class="badge">{{ $prompt->tool }}</span>
        @endif
    </div>
    {{-- Title --}}
    <h3 style="font-size:15px;font-weight:700;color:#131B2E;margin:0 0 10px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $prompt->title }}</h3>
    {{-- Prompt excerpt --}}
    <p style="font-size:12px;color:#787583;margin:0;line-height:1.55;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;font-family:var(--font-mono);">{{ $prompt->prompt }}</p>
</a>
