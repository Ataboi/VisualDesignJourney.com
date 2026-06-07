@extends('layouts.app')

@section('title', $title)
@section('meta_description', $description)

@section('content')
    <section class="vk-container" style="max-width:860px;padding-top:58px;">
        <div class="badge badge-acid" style="margin-bottom:16px;">Visual Design Journey</div>
        <h1 style="margin:0;color:#131B2E;font-size:clamp(36px,7vw,74px);line-height:.98;font-weight:900;">{{ $title }}</h1>
        <p style="margin:22px 0 0;color:#454F5E;font-size:17px;line-height:1.75;">{{ $description }}</p>
        @if($slug === 'sponsor')
            <form method="post" action="{{ route('api.sponsor-lead') }}" class="card-lg" style="margin-top:28px;padding:18px;border-radius:8px;display:grid;gap:12px;">
                @csrf
                <input class="input-field" name="name" placeholder="Name" required>
                <input class="input-field" type="email" name="email" placeholder="Email" required>
                <input class="input-field" name="company" placeholder="Company">
                <input class="input-field" name="budget" placeholder="Budget">
                <textarea class="input-field" name="message" placeholder="Message" rows="5" required></textarea>
                <button class="btn-primary" type="submit">Send</button>
            </form>
        @endif
    </section>
@endsection
