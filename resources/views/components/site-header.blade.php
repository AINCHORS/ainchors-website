<header
    class="sticky top-0 z-50 border-b border-ainchors-grey-light/20 bg-ainchors-white"
    x-data="{ mobileOpen: false, trainingOpen: false, consultingOpen: false, accountOpen: false, mobileTrainingOpen: false, mobileConsultingOpen: false, mobileAccountOpen: false }"
    @keydown.escape.window="mobileOpen = false; trainingOpen = false; consultingOpen = false; accountOpen = false; mobileTrainingOpen = false; mobileConsultingOpen = false; mobileAccountOpen = false"
>
    <div class="mx-auto h-20 max-w-ainchors-container px-4 sm:px-6">
        <div class="flex h-full items-center justify-between gap-4">
            <a href="{{ route('home') }}" aria-label="AINCHORS home" class="flex flex-shrink-0 items-center">
                <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS Training & Consulting" class="h-10 w-auto sm:h-11">
            </a>

            <nav aria-label="Main navigation" class="hidden items-center gap-4 font-sans text-ainchors-body lg:flex xl:gap-6">
                <a href="{{ route('home') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Home</a>
                <a href="{{ route('about') }}" class="text-ainchors-navy transition hover:text-ainchors-green">About Us</a>

                <div class="relative" @mouseenter="trainingOpen = true" @mouseleave="trainingOpen = false" @focusin="trainingOpen = true" @focusout="trainingOpen = false">
                    <button id="training-menu-trigger" type="button" @click="trainingOpen = !trainingOpen" class="flex items-center gap-1 text-ainchors-navy transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green" :aria-expanded="trainingOpen.toString()" aria-controls="training-menu">
                        Training
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                    </button>
                    <div id="training-menu" x-cloak x-show="trainingOpen" x-transition.origin.top.left class="absolute left-0 top-full min-w-56 pt-2" role="menu" aria-labelledby="training-menu-trigger">
                        <div class="rounded-ainchors-card bg-ainchors-white py-2 shadow-lg ring-1 ring-ainchors-grey-light/20">
                            <a role="menuitem" href="{{ route('trainers') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Trainer Profiles</a>
                            <a role="menuitem" href="{{ route('testimonials') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Testimonials</a>
                            <a role="menuitem" href="{{ route('courses.index') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Courses</a>
                            <a role="menuitem" href="{{ route('success-story') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Success Story</a>
                        </div>
                    </div>
                </div>

                <div class="relative" @mouseenter="consultingOpen = true" @mouseleave="consultingOpen = false" @focusin="consultingOpen = true" @focusout="consultingOpen = false">
                    <button id="consulting-menu-trigger" type="button" @click="consultingOpen = !consultingOpen" class="flex items-center gap-1 text-ainchors-navy transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green" :aria-expanded="consultingOpen.toString()" aria-controls="consulting-menu">
                        Consulting
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                    </button>
                    <div id="consulting-menu" x-cloak x-show="consultingOpen" x-transition.origin.top.left class="absolute left-0 top-full min-w-64 pt-2" role="menu" aria-labelledby="consulting-menu-trigger">
                        <div class="rounded-ainchors-card bg-ainchors-white py-2 shadow-lg ring-1 ring-ainchors-grey-light/20">
                            <a role="menuitem" href="{{ route('consulting.main') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Consulting Introduction</a>
                            <a role="menuitem" href="{{ route('consulting.government') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Public / Government Sector</a>
                            <a role="menuitem" href="{{ route('consulting.private') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Private Sector</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('faqs') }}" class="text-ainchors-navy transition hover:text-ainchors-green">FAQ's</a>
                <a href="{{ route('hiring') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Join Us</a>
                <a href="{{ route('contact') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Contact Us</a>
            </nav>

            <div class="hidden flex-shrink-0 items-center gap-3 lg:flex">
                @auth
                    <div class="relative" @click.outside="accountOpen = false">
                        <button id="account-menu-trigger" type="button" @click="accountOpen = !accountOpen" class="flex items-center gap-1 font-sans text-ainchors-body text-ainchors-navy transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green" :aria-expanded="accountOpen.toString()" aria-controls="account-menu">
                            Account
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>
                        <div id="account-menu" x-cloak x-show="accountOpen" x-transition.origin.top.right class="absolute right-0 top-full min-w-48 pt-2" role="menu" aria-labelledby="account-menu-trigger">
                            <div class="rounded-ainchors-card bg-ainchors-white py-2 shadow-lg ring-1 ring-ainchors-grey-light/20">
                                @if (\Illuminate\Support\Facades\Route::has('profile'))
                                    <a role="menuitem" href="{{ route('profile') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">My Profile</a>
                                @endif
                                <a role="menuitem" href="{{ route('my-courses') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">My Courses</a>
                                @if (\Illuminate\Support\Facades\Route::has('purchase-history'))
                                    <a role="menuitem" href="{{ route('purchase-history') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Purchase History</a>
                                @endif
                                @if (auth()->user()->role === 'admin' && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
                                    <a role="menuitem" href="{{ route('admin.dashboard') }}" class="block px-4 py-2 font-semibold text-ainchors-green transition hover:bg-ainchors-green-hero">Admin Dashboard</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" role="menuitem" class="block w-full px-4 py-2 text-left text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Logout</button></form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="font-sans text-ainchors-body text-ainchors-navy transition hover:text-ainchors-green">Login</a>
                    <x-button variant="primary" :href="route('register')">Register</x-button>
                @endauth
            </div>

            <button type="button" @click="mobileOpen = !mobileOpen" class="rounded text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green lg:hidden" :aria-expanded="mobileOpen.toString()" aria-controls="mobile-navigation" aria-label="Toggle navigation">
                <svg x-show="!mobileOpen" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-cloak x-show="mobileOpen" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-navigation" x-cloak x-show="mobileOpen" x-transition.origin.top aria-label="Mobile navigation" class="max-h-[calc(100vh-5rem)] overflow-y-auto border-t border-ainchors-grey-light/20 bg-ainchors-white px-6 py-5 font-sans text-ainchors-body lg:hidden">
        <div class="mx-auto flex max-w-ainchors-container flex-col gap-4">
            <a href="{{ route('home') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Home</a>
            <a href="{{ route('about') }}" class="text-ainchors-navy transition hover:text-ainchors-green">About Us</a>
            <div>
                <button type="button" @click="mobileTrainingOpen = !mobileTrainingOpen" class="flex w-full items-center justify-between text-left text-ainchors-navy transition hover:text-ainchors-green" :aria-expanded="mobileTrainingOpen.toString()" aria-controls="mobile-training-menu">Training <span aria-hidden="true">⌄</span></button>
                <div id="mobile-training-menu" x-cloak x-show="mobileTrainingOpen" x-transition class="ml-4 mt-3 flex flex-col gap-3 border-l border-ainchors-green pl-4">
                    <a href="{{ route('trainers') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Trainer Profiles</a>
                    <a href="{{ route('testimonials') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Testimonials</a>
                    <a href="{{ route('courses.index') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Courses</a>
                    <a href="{{ route('success-story') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Success Story</a>
                </div>
            </div>
            <div>
                <button type="button" @click="mobileConsultingOpen = !mobileConsultingOpen" class="flex w-full items-center justify-between text-left text-ainchors-navy transition hover:text-ainchors-green" :aria-expanded="mobileConsultingOpen.toString()" aria-controls="mobile-consulting-menu">Consulting <span aria-hidden="true">⌄</span></button>
                <div id="mobile-consulting-menu" x-cloak x-show="mobileConsultingOpen" x-transition class="ml-4 mt-3 flex flex-col gap-3 border-l border-ainchors-green pl-4">
                    <a href="{{ route('consulting.main') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Consulting Introduction</a>
                    <a href="{{ route('consulting.government') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Public / Government Sector</a>
                    <a href="{{ route('consulting.private') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Private Sector</a>
                </div>
            </div>
            <a href="{{ route('faqs') }}" class="text-ainchors-navy transition hover:text-ainchors-green">FAQ's</a>
            <a href="{{ route('hiring') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Join Us</a>
            <a href="{{ route('contact') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Contact Us</a>
            @auth
                <div class="border-t border-ainchors-grey-light/20 pt-4">
                    <button type="button" @click="mobileAccountOpen = !mobileAccountOpen" class="flex w-full items-center justify-between text-left text-ainchors-navy" :aria-expanded="mobileAccountOpen.toString()" aria-controls="mobile-account-menu">Account <span aria-hidden="true">⌄</span></button>
                    <div id="mobile-account-menu" x-cloak x-show="mobileAccountOpen" x-transition class="ml-4 mt-3 flex flex-col gap-3 border-l border-ainchors-green pl-4">
                        @if (\Illuminate\Support\Facades\Route::has('profile'))<a href="{{ route('profile') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">My Profile</a>@endif
                        <a href="{{ route('my-courses') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">My Courses</a>
                        @if (\Illuminate\Support\Facades\Route::has('purchase-history'))<a href="{{ route('purchase-history') }}" class="text-ainchors-grey-dark hover:text-ainchors-green">Purchase History</a>@endif
                        @if (auth()->user()->role === 'admin' && \Illuminate\Support\Facades\Route::has('admin.dashboard'))<a href="{{ route('admin.dashboard') }}" class="font-semibold text-ainchors-green">Admin Dashboard</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-ainchors-grey-dark hover:text-ainchors-green">Logout</button></form>
                    </div>
                </div>
            @else
                <div class="flex flex-wrap items-center gap-4 border-t border-ainchors-grey-light/20 pt-4">
                    <a href="{{ route('login') }}" class="text-ainchors-navy hover:text-ainchors-green">Login</a>
                    <x-button variant="primary" :href="route('register')">Register</x-button>
                </div>
            @endauth
        </div>
    </nav>
</header>
