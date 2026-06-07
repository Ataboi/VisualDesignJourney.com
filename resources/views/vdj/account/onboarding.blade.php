@extends('layouts.app')

@section('title', 'Onboarding')
@section('meta_description', 'Set up your Visual Design Journey profile.')

@section('content')
    <section class="vk-container" style="max-width:680px;padding-top:64px;">
        <div class="card-lg" style="padding:24px;border-radius:8px;">
            <div class="badge badge-acid" style="margin-bottom:14px;">Welcome</div>
            <h1 style="margin:0 0 12px;color:#131B2E;font-size:42px;font-weight:900;">Set up your profile</h1>
            <form method="post" action="{{ route('onboarding.finish') }}" style="display:grid;gap:14px;margin-top:22px;">
                @csrf
                <textarea class="input-field" name="bio" rows="4" placeholder="Short bio">{{ old('bio', $user->bio) }}</textarea>
                <input class="input-field" name="style_tags" placeholder="Favorite styles: Swiss, Brutalist, Minimal">
                <button class="btn-primary" type="submit">Finish</button>
            </form>
        </div>
    </section>
@endsection
