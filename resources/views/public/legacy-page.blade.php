@extends('layouts.app')

@section('title', $title)

@section('content')
    <section class="bg-ainchors-white">
        <iframe
            id="legacy-page-frame"
            src="{{ $legacySource }}"
            title="{{ $title }}"
            class="block min-h-[900px] w-full border-0"
            scrolling="no"
            loading="eager"
        ></iframe>
    </section>

    <script>
        window.addEventListener('message', function (event) {
            if (event.origin !== window.location.origin || event.data?.source !== 'ainchors-legacy' || event.data?.type !== 'height') return;
            const frame = document.getElementById('legacy-page-frame');
            if (frame && Number.isFinite(event.data.height)) frame.style.height = Math.max(900, event.data.height) + 'px';
        });
    </script>
@endsection
