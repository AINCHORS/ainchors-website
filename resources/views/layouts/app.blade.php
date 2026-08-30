<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AINCHORS Training & Consulting">
    <title>@yield('title', 'AINCHORS Training & Consulting')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <x-site-header />
    <main id="main-content">
        @yield('content')
    </main>
    <x-welcome-modal />
    <x-profile-completion-modal />
    <x-site-footer />
    <x-ai-assistant />
</body>
</html>
