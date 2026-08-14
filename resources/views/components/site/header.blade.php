@props(['navigation'])

<header class="site-header" x-data="{ mobileOpen: false }">
    <div class="site-shell header-inner">
        <a href="{{ url('/home') }}" class="brand" aria-label="AINCHORS home">
            <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS Training & Consulting">
        </a>
        <button class="menu-toggle" type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-controls="main-navigation">
            <span class="sr-only">Open navigation</span><i></i><i></i><i></i>
        </button>
        <nav id="main-navigation" class="main-navigation" :class="{ 'is-open': mobileOpen }" aria-label="Main navigation" @keydown.escape.window="mobileOpen = false">
            @foreach ($navigation as $item)
                @if (! empty($item['children']))
                    <div class="nav-group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                        <div class="nav-parent">
                            <a href="{{ url($item['url']) }}">{{ $item['label'] }}</a>
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-label="Open {{ $item['label'] }} menu">⌄</button>
                        </div>
                        <div class="nav-dropdown" x-cloak x-show="open" x-transition.origin.top.left>
                            @foreach ($item['children'] as $child)
                                <a href="{{ url($child['url']) }}">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ url($item['url']) }}" @class(['nav-featured' => $item['featured'] ?? false])>{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>
    </div>
</header>
