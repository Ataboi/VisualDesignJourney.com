@extends('layouts.app')

@section('title', 'Unsubscribe')
@section('meta_description', 'Unsubscribe from the Visual Design Journey newsletter.')

@section('content')
    <section class="vk-container" style="max-width:560px;padding-top:64px;">
        <div class="card-lg" style="padding:24px;border-radius:8px;text-align:center;">
            <h1 style="margin:0 0 12px;color:#131B2E;font-size:34px;font-weight:900;">
                {{ $subscriber ? 'You have been unsubscribed' : 'Invalid unsubscribe link' }}
            </h1>
            <p style="color:#454F5E;">{{ $subscriber ? 'You will no longer receive newsletter emails from Visual Design Journey.' : 'Please use the unsubscribe link from your newsletter email.' }}</p>
            <a class="btn-primary" href="{{ route('home') }}">Back home</a>
        </div>
    </section>
@endsection
