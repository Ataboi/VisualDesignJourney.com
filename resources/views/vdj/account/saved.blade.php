@extends('layouts.app')

@section('title', 'Saved Boards')
@section('meta_description', 'Your saved Visual Design Journey boards.')

@section('content')
    <section class="vk-container" style="padding-top:54px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:22px;">
            <div>
                <div class="badge badge-acid" style="margin-bottom:14px;">Library</div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,68px);font-weight:900;">Saved boards</h1>
            </div>
            <a href="{{ route('home') }}" class="btn-secondary">Explore</a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
            @forelse($boards as $board)
                @include('vdj.partials.board-card', ['board' => $board])
            @empty
                <div class="card-lg" style="grid-column:1/-1;padding:34px;border-radius:8px;color:#454F5E;">No saved boards yet.</div>
            @endforelse
        </div>

        @if(method_exists($boards, 'links'))
            <div style="margin-top:24px;">{{ $boards->links() }}</div>
        @endif
    </section>
@endsection
