<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Title & Description --}}
    <title>@yield('title', 'Visual Design Journey') — Visual Design Journey</title>
    <meta name="description" content="@yield('meta_description', 'Visual Design Journey is a curated design archive, visual board community, and practical tool hub for modern designers.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Visual Design Journey">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'Visual Design Journey')">
    <meta property="og:description" content="@yield('og_description', 'Curated design boards, editorial resources, and practical tools for visual designers.')">
    @stack('og')

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..800;1,14..32,300..400&family=Manrope:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @stack('fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')

    @if(config('adsense.ga4_measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('adsense.ga4_measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('adsense.ga4_measurement_id') }}');
        </script>
    @endif
    @if(config('adsense.client'))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('adsense.client') }}" crossorigin="anonymous"></script>
    @endif
</head>
<body class="min-h-screen">
    @include('partials.nav')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
    @stack('scripts')
</body>
</html>
