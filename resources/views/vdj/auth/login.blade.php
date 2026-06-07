@extends('layouts.app')

@section('title', 'Login')
@section('meta_description', 'Login to Visual Design Journey.')

@section('content')
    <section class="vk-container" style="max-width:520px;padding-top:64px;">
        <div class="card-lg" style="padding:24px;border-radius:8px;">
            <h1 style="margin:0 0 18px;color:#131B2E;font-size:34px;font-weight:900;">Login</h1>
            <form method="post" action="{{ route('login') }}" style="display:grid;gap:14px;">
                @csrf
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Email or username</label>
                    <input class="input-field" name="login" value="{{ old('login') }}" required autocomplete="username">
                    @error('login') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#454F5E;font-size:12px;">Password</label>
                    <input class="input-field" type="password" name="password" required autocomplete="current-password">
                    @error('password') <p style="color:#FF4D4D;font-size:12px;margin:7px 0 0;">{{ $message }}</p> @enderror
                </div>
                <label style="display:flex;gap:8px;align-items:center;color:#454F5E;font-size:13px;">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <button class="btn-primary" type="submit" style="justify-content:center;">Login</button>
            </form>
            <p style="margin:18px 0 0;color:#454F5E;font-size:13px;">New here? <a href="{{ route('register') }}" style="color:#7F77DD;">Create an account</a></p>
        </div>
    </section>
@endsection
