@extends('layouts.app')

@section('title', 'Email Preferences')
@section('meta_description', 'Manage Visual Design Journey newsletter preferences.')

@section('content')
    <section class="vk-container" style="max-width:560px;padding-top:64px;">
        <div class="card-lg" style="padding:24px;border-radius:8px;text-align:center;">
            <h1 style="margin:0 0 12px;color:#131B2E;font-size:34px;font-weight:900;">Email preferences</h1>
            @if($subscriber)
                @if(session('status')) <div class="badge badge-success" style="margin-bottom:14px;">{{ session('status') }}</div> @endif
                <p style="color:#454F5E;">{{ \Illuminate\Support\Str::mask($subscriber->email, '*', 1, max(strlen($subscriber->email) - 8, 3)) }}</p>
                <p class="badge {{ $subscriber->status === 'active' ? 'badge-success' : 'badge-error' }}">{{ $subscriber->status }}</p>
                <form method="post" action="{{ route('newsletter-prefs.update') }}" style="display:grid;gap:10px;margin-top:18px;">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button class="btn-secondary" name="action" value="unsubscribe" type="submit">Unsubscribe</button>
                    <button class="btn-primary" name="action" value="resubscribe" type="submit">Re-subscribe</button>
                </form>
            @else
                <p style="color:#454F5E;">This preference link is invalid or expired.</p>
            @endif
        </div>
    </section>
@endsection
