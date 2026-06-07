@extends('layouts.app')

@section('title', 'Settings')
@section('meta_description', 'Update your Visual Design Journey account settings.')

@section('content')
    <section class="vk-container" style="max-width:920px;padding-top:54px;">
        <h1 style="margin:0 0 22px;color:#131B2E;font-size:clamp(36px,7vw,68px);font-weight:900;">Settings</h1>

        @if(session('status'))
            <div class="badge badge-success" style="margin-bottom:16px;">{{ session('status') }}</div>
        @endif

        <div style="display:grid;grid-template-columns:220px minmax(0,1fr);gap:18px;">
            <nav class="card-lg" style="padding:12px;border-radius:8px;display:grid;gap:6px;align-self:start;">
                <a class="filter-pill {{ $section === 'profile' ? 'active' : '' }}" href="{{ route('settings', ['section' => 'profile']) }}">Profile</a>
                <a class="filter-pill {{ $section === 'password' ? 'active' : '' }}" href="{{ route('settings', ['section' => 'password']) }}">Password</a>
            </nav>

            <form method="post" action="{{ route('settings.update') }}" class="card-lg" style="padding:22px;border-radius:8px;display:grid;gap:14px;">
                @csrf
                <input type="hidden" name="section" value="{{ $section }}">

                @if($section === 'password')
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Current password</label>
                        <input class="input-field" type="password" name="current_password" required>
                        @error('current_password') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">New password</label>
                        <input class="input-field" type="password" name="password" required>
                        @error('password') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Confirm password</label>
                        <input class="input-field" type="password" name="password_confirmation" required>
                    </div>
                @else
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Username</label>
                        <input class="input-field" name="username" value="{{ old('username', $user->username) }}" required>
                        @error('username') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Bio</label>
                        <textarea class="input-field" name="bio" rows="4">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Website</label>
                            <input class="input-field" name="website" value="{{ old('website', $user->website) }}">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Location</label>
                            <input class="input-field" name="location" value="{{ old('location', $user->location) }}">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Avatar path or URL</label>
                        <input class="input-field" name="avatar" value="{{ old('avatar', $user->avatar) }}">
                    </div>
                @endif

                <button class="btn-primary" type="submit">Save settings</button>
            </form>
        </div>
    </section>
@endsection
