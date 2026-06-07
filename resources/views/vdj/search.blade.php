@extends('layouts.app')

@section('title', $term ? 'Search: '.$term : 'Search')
@section('meta_description', 'Search Visual Design Journey boards and design articles.')

@section('content')
    <section class="vk-container" style="padding-top:54px;">
        <h1 style="margin:0 0 22px;color:#131B2E;font-size:clamp(36px,7vw,72px);font-weight:900;">Search</h1>
        <form action="{{ route('search') }}" method="get" class="card-lg" style="padding:18px;border-radius:8px;margin-bottom:34px;">
            <div style="display:flex;gap:10px;">
                <input name="q" value="{{ $term }}" class="input-field" placeholder="Search boards and articles" style="min-width:0;">
                <button class="btn-primary" type="submit">Search</button>
            </div>
        </form>

        @if($term)
            <h2 style="margin:0 0 16px;color:#131B2E;font-size:24px;font-weight:800;">Boards</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-bottom:44px;">
                @forelse($boards as $board)
                    @include('vdj.partials.board-card', ['board' => $board])
                @empty
                    <div class="card-lg" style="grid-column:1/-1;padding:24px;border-radius:8px;color:#454F5E;">No board results.</div>
                @endforelse
            </div>

            <h2 style="margin:0 0 16px;color:#131B2E;font-size:24px;font-weight:800;">Articles</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
                @forelse($posts as $post)
                    @include('vdj.partials.post-card', ['post' => $post, 'locale' => 'en'])
                @empty
                    <div class="card-lg" style="grid-column:1/-1;padding:24px;border-radius:8px;color:#454F5E;">No article results.</div>
                @endforelse
            </div>
        @endif
    </section>
@endsection
