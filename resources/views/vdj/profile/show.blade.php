@extends('layouts.app')

@php
    use App\Support\Vdj;
@endphp

@section('title', '@'.$user->username)
@section('meta_description', Vdj::plainText($user->bio ?: 'Visual Design Journey curator profile.', 160))

@section('content')
    <section class="vk-container" style="padding-top:54px;">
        <div style="display:flex;gap:18px;align-items:center;margin-bottom:32px;">
            <div style="width:82px;height:82px;border-radius:8px;background:#F2F3FF;border:.5px solid #E9ECEF;overflow:hidden;display:grid;place-items:center;color:#454F5E;font-weight:800;">
                @if($avatar = Vdj::imageUrl($user->avatar))
                    <img src="{{ $avatar }}" alt="{{ $user->username }}" style="width:100%;height:100%;object-fit:cover;">
                @else
                    {{ strtoupper(substr($user->username, 0, 1)) }}
                @endif
            </div>
            <div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(34px,6vw,64px);font-weight:900;">{{ $user->username }}</h1>
                @if($user->bio)
                    <p style="max-width:680px;margin:10px 0 0;color:#454F5E;line-height:1.7;">{{ $user->bio }}</p>
                @endif
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;">
            @forelse($user->boards as $board)
                @include('vdj.partials.board-card', ['board' => $board])
            @empty
                <div class="card-lg" style="grid-column:1/-1;padding:34px;border-radius:8px;color:#454F5E;">No public boards yet.</div>
            @endforelse
        </div>
    </section>
@endsection
