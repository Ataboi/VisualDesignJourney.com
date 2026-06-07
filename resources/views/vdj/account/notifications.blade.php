@extends('layouts.app')

@section('title', 'Notifications')
@section('meta_description', 'Your Visual Design Journey notifications.')

@section('content')
    <section class="vk-container" style="max-width:820px;padding-top:54px;">
        <div style="display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:22px;">
            <div>
                <div class="badge badge-acid" style="margin-bottom:14px;">Inbox</div>
                <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,68px);font-weight:900;">Notifications</h1>
            </div>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read') }}">
                    @csrf
                    <button class="btn-secondary" type="submit">Mark all read</button>
                </form>
            @endif
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
            @foreach(['all' => 'All', 'like' => 'Likes', 'follow' => 'Follows', 'comment' => 'Comments', 'mention' => 'Mentions'] as $key => $label)
                <a class="filter-pill {{ $type === $key ? 'active' : '' }}" href="{{ route('notifications', ['type' => $key]) }}">{{ $label }}</a>
            @endforeach
        </div>

        <div style="display:grid;gap:10px;">
            @forelse($notifications as $notification)
                <article class="card-lg" style="padding:15px;border-radius:8px;border-left:3px solid {{ $notification->is_read ? '#E9ECEF' : '#7F77DD' }};">
                    <p style="margin:0;color:#131B2E;font-weight:700;">{{ ucfirst($notification->type) }}</p>
                    <p style="margin:6px 0 0;color:#454F5E;font-size:13px;">
                        @if($notification->fromUser)
                            {{ '@'.$notification->fromUser->username }}
                        @else
                            Visual Design Journey
                        @endif
                        @if($notification->board)
                            · <a href="{{ \App\Support\Vdj::boardUrl($notification->board) }}" style="color:#7F77DD;">{{ $notification->board->title }}</a>
                        @endif
                    </p>
                    <p style="margin:7px 0 0;color:#787583;font-size:12px;">{{ optional($notification->created_at)->diffForHumans() }}</p>
                </article>
            @empty
                <div class="card-lg" style="padding:34px;border-radius:8px;color:#454F5E;">No notifications yet.</div>
            @endforelse
        </div>

        <div style="margin-top:20px;">{{ $notifications->links() }}</div>
    </section>
@endsection
