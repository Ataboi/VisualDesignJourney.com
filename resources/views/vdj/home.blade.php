@extends('layouts.app')

@section('title', 'Visual Design Journey')
@section('meta_description', 'Explore curated design boards, visual systems, design tools, and practical creative resources from Visual Design Journey.')

@section('content')
    <section style="background:#FFFFFF;border-bottom:.5px solid #E9ECEF;">
        <div class="vk-container" style="padding-top:80px;padding-bottom:72px;">
            <div class="home-hero-grid">
                <div>
                    <p class="editorial-eyebrow" style="margin:0 0 22px;">Visual archive</p>
                    <h1 class="hero-title" style="max-width:820px;margin:0;">
                        A quieter way to collect, study, and build visual systems.
                    </h1>
                    <p style="max-width:620px;margin:24px 0 0;color:#454F5E;font-size:18px;line-height:1.6;">
                        Curated boards, design writing, and practical VibeKit tools now share one editorial workspace without losing the original VDJ archive.
                    </p>
                </div>

                <form action="{{ route('search') }}" method="get" class="card-lg" style="padding:24px;">
                    <label for="q" class="editorial-eyebrow" style="display:block;margin-bottom:14px;">Search the archive</label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input id="q" name="q" class="input-field" placeholder="boards, blog, styles..." style="min-width:0;">
                        <button class="btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="vk-container" style="padding-top:72px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:24px;">
            <div>
                <p class="editorial-eyebrow" style="margin:0 0 10px;">Boards</p>
                <h2 class="section-title" style="margin:0;">Fresh visual references</h2>
            </div>
            <a href="{{ route('tools') }}" class="btn-secondary">Open tools hub</a>
        </div>

        @if($boards->count())
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:24px;">
                @foreach($boards as $board)
                    @include('vdj.partials.board-card', ['board' => $board])
                @endforeach
            </div>
            <div style="margin-top:32px;">{{ $boards->links() }}</div>
        @else
            <div class="card-lg" style="padding:34px;color:#454F5E;">No boards found yet. Connect the existing VDJ database and this grid will hydrate from the preserved tables.</div>
        @endif
    </section>

    <section class="vk-container" style="padding-top:80px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:24px;">
            <div>
                <p class="editorial-eyebrow" style="margin:0 0 10px;">Editorial</p>
                <h2 class="section-title" style="margin:0;">Latest design articles</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="btn-secondary">View blog</a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;">
            @forelse($posts as $post)
                @include('vdj.partials.post-card', ['post' => $post, 'locale' => 'en'])
            @empty
                <div class="card-lg" style="grid-column:1/-1;padding:34px;color:#454F5E;">No blog posts found yet.</div>
            @endforelse
        </div>
    </section>
@endsection
