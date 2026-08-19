<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AINCHORS Training & Consulting">
    <title>@yield('title', 'AINCHORS')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ainchors-green-hero text-ainchors-navy antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-ainchors-grey-light/20 bg-ainchors-white/95 backdrop-blur">
            <div class="mx-auto flex min-h-20 max-w-ainchors-container items-center justify-between gap-4 px-5 sm:px-6">
                <a href="{{ route('home') }}" aria-label="AINCHORS home" class="flex shrink-0 items-center">
                    <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS Training & Consulting" class="h-10 w-auto sm:h-11">
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-sans text-sm font-semibold text-ainchors-navy transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to website
                </a>
            </div>
        </header>
        <main id="main-content" class="flex flex-1 flex-col">@yield('content')</main>
        <footer class="border-t border-ainchors-grey-light/20 bg-ainchors-white px-5 py-5 sm:px-6">
            <div class="mx-auto flex max-w-ainchors-container flex-col items-center justify-between gap-3 font-sans text-xs text-ainchors-grey-dark sm:flex-row">
                <span>AINCHORS Training &amp; Consulting</span>
                <div class="flex items-center gap-4">
                    <a href="{{ route('terms') }}" class="transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Terms &amp; Conditions</a>
                    <a href="{{ route('privacy') }}" class="transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Privacy Policy</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
