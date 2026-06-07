@extends('layouts.app')

@section('title', '@'.$user->username.' followers')
@section('meta_description', 'Followers and following for '.$user->username.' on Visual Design Journey.')

@section('content')
    <section class="vk-container" style="padding-top:54px;">
        <h1 style="margin:0 0 22px;color:#131B2E;font-size:clamp(36px,7vw,68px);font-weight:900;">{{ '@'.$user->username }}</h1>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            @foreach(['Followers' => $followers, 'Following' => $following] as $label => $rows)
                <div class="card-lg" style="padding:18px;border-radius:8px;">
                    <h2 style="margin:0 0 14px;color:#131B2E;font-size:20px;font-weight:800;">{{ $label }}</h2>
                    <div style="display:grid;gap:10px;">
                        @forelse($rows as $row)
                            @php($person = $label === 'Followers' ? $row->follower : $row->following)
                            @if($person)
                                <a href="{{ route('profile.show', ['username' => $person->username]) }}" style="display:flex;align-items:center;gap:10px;color:#131B2E;text-decoration:none;">
                                    <span style="width:34px;height:34px;border-radius:8px;background:#F2F3FF;display:grid;place-items:center;color:#454F5E;font-weight:800;">{{ strtoupper(substr($person->username, 0, 1)) }}</span>
                                    <span>{{ $person->username }}</span>
                                </a>
                            @endif
                        @empty
                            <p style="margin:0;color:#454F5E;">No {{ strtolower($label) }} yet.</p>
                        @endforelse
                    </div>
                    <div style="margin-top:14px;">{{ $rows->links() }}</div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
