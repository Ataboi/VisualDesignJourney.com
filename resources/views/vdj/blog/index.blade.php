@extends('layouts.app')

@php
    $prefix = $locale === 'tr' ? 'Turkish ' : ($locale === 'de' ? 'German ' : '');
@endphp

@section('title', $prefix.'Design Blog')
@section('meta_description', 'Visual Design Journey design articles, visual culture notes, tools, workflows, and creative references.')

@section('content')
    <section class="vk-container" style="padding-top:54px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:28px;">
            <div>
                <div class="badge badge-acid" style="margin-bottom:16px;">Editorial archive</div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,76px);line-height:.96;font-weight:900;">Design blog</h1>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="filter-pill {{ $locale === 'en' ? 'active' : '' }}" href="{{ route('blog.index') }}">EN</a>
                <a class="filter-pill {{ $locale === 'tr' ? 'active' : '' }}" href="{{ route('blog.localized.index', ['lang' => 'tr']) }}">TR</a>
                <a class="filter-pill {{ $locale === 'de' ? 'active' : '' }}" href="{{ route('blog.localized.index', ['lang' => 'de']) }}">DE</a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            @forelse($posts as $post)
                @include('vdj.partials.post-card', ['post' => $post, 'locale' => $locale])
            @empty
                <div class="card-lg" style="grid-column:1/-1;padding:34px;border-radius:8px;color:#454F5E;">No posts found.</div>
            @endforelse
        </div>

        @if(method_exists($posts, 'links'))
            <div style="margin-top:28px;">{{ $posts->links() }}</div>
        @endif
    </section>
@endsection
