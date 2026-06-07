@extends('layouts.app')

@section('title', 'VibeKit')
@section('meta_description', 'Free design tools, prompts and visual generators for modern designers. Font pairings, color palettes, design prompts, website section gallery and brief generators.')

@push('fonts')
@php
$fontFamilies = $featuredFonts->flatMap(fn($p) => [
    urlencode($p->heading_font) . ':wght@' . $p->heading_weight,
    urlencode($p->body_font) . ':wght@' . $p->body_weight,
])->unique()->values();
@endphp
@if($fontFamilies->isNotEmpty())
<link href="https://fonts.googleapis.com/css2?{{ $fontFamilies->map(fn($f) => 'family=' . $f)->implode('&') }}&display=swap" rel="stylesheet">
@endif
@endpush

@section('content')

{{-- Hero --}}
<section style="background:#FFFFFF;border-bottom:.5px solid #E9ECEF;">
    <div class="vk-container" style="padding-top:80px;padding-bottom:80px;">
        <p class="editorial-eyebrow" style="margin:0 0 22px;">VibeKit tools</p>
        <h1 class="hero-title" style="max-width:820px;margin:0;">
            Design tools, prompts, and visual systems in one editorial workspace.
        </h1>
        <p style="font-size:18px;color:#454F5E;max-width:620px;margin:24px 0 34px;line-height:1.6;">
            Font pairings, color palettes, design prompts, section inspiration, and brief generators with the same quiet VDJ visual language.
        </p>

        <form method="GET" action="{{ route('design-prompts.index') }}" class="card-lg" style="max-width:640px;padding:12px;display:flex;gap:10px;align-items:center;">
            <input type="text" name="search" class="input-field" placeholder="Search prompts, palettes, sections..." style="border:none;min-width:0;">
            <button type="submit" class="btn-primary">Search</button>
        </form>
    </div>
</section>

{{-- Divider --}}
<div style="margin:0;"></div>

{{-- Tools Grid --}}
<section class="vk-container" style="padding-top:80px;padding-bottom:80px;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Everything in one place</p>
            <h2 class="section-title" style="margin:0;">5 free tools for designers</h2>
        </div>
        <a href="{{ route('tools') }}" style="font-size:13px;color:#574EB1;text-decoration:none;font-weight:500;" onmouseover="this.style.color='#7067CC'" onmouseout="this.style.color='#574EB1'">View all tools →</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
        $tools = [
            ['name' => 'Font Pairing Finder', 'desc' => 'Curated heading + body font combinations with live previews.', 'href' => route('font-pairings.index'), 'icon' => 'Aa'],
            ['name' => 'Color Palette Generator', 'desc' => '5-color palettes with mood tags and one-click hex copy.', 'href' => route('color-palettes.index'), 'icon' => '◑'],
            ['name' => 'Design Prompt Library', 'desc' => 'Skill-building challenges sorted by type and difficulty.', 'href' => route('design-prompts.index'), 'icon' => '✦'],
            ['name' => 'Website Section Gallery', 'desc' => 'Real-world section inspiration: heroes, pricing, navbars.', 'href' => route('website-sections.index'), 'icon' => '▦'],
            ['name' => 'Landing Page Brief Generator', 'desc' => 'Fill a template, get a structured design brief instantly.', 'href' => route('briefs.index'), 'icon' => '⊡'],
        ];
        @endphp
        @foreach($tools as $tool)
        <a href="{{ $tool['href'] }}" class="card" style="padding:24px;text-decoration:none;display:block;">
            <div style="display:flex;align-items:flex-start;gap:16px;">
                <div style="width:40px;height:40px;border-radius:10px;background-color:#F2F3FF;border:.5px solid #E9ECEF;display:flex;align-items:center;justify-content:center;font-size:16px;color:#7F77DD;flex-shrink:0;font-weight:800;">{{ $tool['icon'] }}</div>
                <div>
                    <h3 style="font-size:15px;font-weight:700;color:#131B2E;margin:0 0 4px;line-height:1.2;">{{ $tool['name'] }}</h3>
                    <p style="font-size:13px;color:#787583;margin:0;line-height:1.5;">{{ $tool['desc'] }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</section>

{{-- Featured Font Pairings --}}
@if($featuredFonts->isNotEmpty())
<section class="vk-container" style="padding-bottom:96px;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Typography</p>
            <h2 class="section-title" style="margin:0;">Featured Font Pairings</h2>
        </div>
        <a href="{{ route('font-pairings.index') }}" style="font-size:13px;color:#574EB1;text-decoration:none;font-weight:500;" onmouseover="this.style.color='#7067CC'" onmouseout="this.style.color='#574EB1'">View all →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($featuredFonts as $pair)
            @include('partials.font-card', ['pair' => $pair])
        @endforeach
    </div>
</section>
@endif

{{-- Popular Color Palettes --}}
@if($featuredPalettes->isNotEmpty())
<section class="vk-container" style="padding-bottom:96px;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Color</p>
            <h2 class="section-title" style="margin:0;">Popular Color Palettes</h2>
        </div>
        <a href="{{ route('color-palettes.index') }}" style="font-size:13px;color:#574EB1;text-decoration:none;font-weight:500;" onmouseover="this.style.color='#7067CC'" onmouseout="this.style.color='#574EB1'">View all →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($featuredPalettes as $palette)
            @include('partials.palette-card', ['palette' => $palette])
        @endforeach
    </div>
</section>
@endif

{{-- Latest Design Prompts --}}
@if($latestPrompts->isNotEmpty())
<section class="vk-container" style="padding-bottom:96px;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Prompts</p>
            <h2 class="section-title" style="margin:0;">Latest Design Prompts</h2>
        </div>
        <a href="{{ route('design-prompts.index') }}" style="font-size:13px;color:#574EB1;text-decoration:none;font-weight:500;" onmouseover="this.style.color='#7067CC'" onmouseout="this.style.color='#574EB1'">View all →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($latestPrompts as $prompt)
            @include('partials.prompt-card', ['prompt' => $prompt])
        @endforeach
    </div>
</section>
@endif

{{-- Website Section Inspiration --}}
@if($featuredSections->isNotEmpty())
<section class="vk-container" style="padding-bottom:96px;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 8px;">Sections</p>
            <h2 class="section-title" style="margin:0;">Section Inspiration</h2>
        </div>
        <a href="{{ route('website-sections.index') }}" style="font-size:13px;color:#574EB1;text-decoration:none;font-weight:500;" onmouseover="this.style.color='#7067CC'" onmouseout="this.style.color='#574EB1'">View all →</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($featuredSections as $section)
            @include('partials.section-card', ['section' => $section])
        @endforeach
    </div>
</section>
@endif

@endsection
