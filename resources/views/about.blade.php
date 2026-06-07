@extends('layouts.app')
@section('title', 'About VibeKit')
@section('meta_description', 'VibeKit is a free design utility network — font pairings, color palettes, design prompts, section gallery, and brief generators for modern designers.')

@section('content')
<div class="vk-container" style="padding-top:80px;padding-bottom:96px;max-width:800px;">

    <div style="text-align:center;margin-bottom:64px;">
        <h1 class="page-title" style="margin:0 0 16px;">About Vibe<span style="color:#7F77DD;">Kit</span></h1>
        <p style="font-size:17px;color:#454F5E;line-height:1.65;max-width:520px;margin:0 auto;">A free design utility network built for modern designers who move fast and think visually.</p>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;margin-bottom:56px;">
        <div class="card" style="padding:32px;">
            <h2 style="font-size:16px;font-weight:700;color:#131B2E;margin:0 0 10px;">What is VibeKit?</h2>
            <p style="font-size:14px;color:#454F5E;line-height:1.7;margin:0;">VibeKit is a curated collection of free design tools — font pairings, color palettes, design prompts, website section inspiration, and brief generators — all in one place. No sign-up required. No paywalls. Just design resources, ready to use.</p>
        </div>
        <div class="card" style="padding:32px;">
            <h2 style="font-size:16px;font-weight:700;color:#131B2E;margin:0 0 10px;">Part of VisualDesignJourney</h2>
            <p style="font-size:14px;color:#454F5E;line-height:1.7;margin:0;">VibeKit is part of the <a href="https://visualdesignjourney.com" target="_blank" rel="noopener noreferrer" style="color:#574EB1;text-decoration:none;">VisualDesignJourney</a> ecosystem — a platform dedicated to growing designers through practical resources, inspiration, and community.</p>
        </div>
        <div class="card" style="padding:32px;">
            <h2 style="font-size:16px;font-weight:700;color:#131B2E;margin:0 0 10px;">Always free</h2>
            <p style="font-size:14px;color:#454F5E;line-height:1.7;margin:0;">Every tool on VibeKit is free to use forever. No account needed. Just open, explore, and create better visual systems.</p>
        </div>
    </div>

    <div style="text-align:center;">
        <a href="{{ route('home') }}" class="btn-primary" style="font-size:15px;padding:14px 28px;">Explore the Tools →</a>
    </div>
</div>
@endsection
