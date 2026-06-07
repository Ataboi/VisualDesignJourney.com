@extends('layouts.app')

@section('title', 'Register')
@section('meta_description', 'Create a Visual Design Journey account.')

@section('content')
    <section class="vk-container" style="max-width:560px;padding-top:64px;">
        <div class="card-lg" style="padding:24px;border-radius:8px;">
            <h1 style="margin:0 0 18px;color:#131B2E;font-size:34px;font-weight:900;">Create account</h1>
            <form method="post" action="{{ route('register') }}" style="display:grid;gap:14px;">
                @csrf
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Username</label>
                    <input class="input-field" name="username" value="{{ old('username') }}" required autocomplete="username">
                    @error('username') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Email</label>
                    <input class="input-field" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Password</label>
                    <input class="input-field" type="password" name="password" required autocomplete="new-password">
                    @error('password') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Confirm password</label>
                    <input class="input-field" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <button class="btn-primary" type="submit" style="justify-content:center;">Create account</button>
            </form>
            <p style="margin:18px 0 0;color:#454F5E;font-size:13px;">Already have an account? <a href="{{ route('login') }}" style="color:#7F77DD;">Login</a></p>
        </div>
    </section>
@endsection
