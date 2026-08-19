<header
    class="sticky top-0 z-50 border-b border-ainchors-grey-light/20 bg-ainchors-white"
    x-data="{ mobileOpen: false, trainingOpen: false, consultingOpen: false }"
    @keydown.escape.window="mobileOpen = false; trainingOpen = false; consultingOpen = false"
>
    <div class="mx-auto h-20 max-w-ainchors-container px-4 sm:px-6">
        <div class="flex h-full items-center justify-between gap-4">
            <a href="{{ route('home') }}" aria-label="AINCHORS home" class="flex flex-shrink-0 items-center">
                <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS Training & Consulting" class="h-10 w-auto sm:h-11">
            </a>

            <nav aria-label="Main navigation" class="hidden items-center gap-5 font-sans text-ainchors-body lg:flex xl:gap-8">
                <a href="{{ route('home') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Home</a>
                <a href="{{ url('/about-us-814253') }}" class="text-ainchors-navy transition hover:text-ainchors-green">About us</a>

                <div
                    class="relative"
                    @mouseenter="trainingOpen = true"
                    @mouseleave="trainingOpen = false"
                    @focusin="trainingOpen = true"
                    @focusout="trainingOpen = false"
                >
                    <button type="button" class="flex items-center gap-1 text-ainchors-navy transition hover:text-ainchors-green" :aria-expanded="trainingOpen.toString()">
                        Training
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-cloak x-show="trainingOpen" x-transition.origin.top.left class="absolute left-0 top-full min-w-[210px] pt-2">
                        <div class="rounded-ainchors-card bg-ainchors-white py-2 shadow-lg ring-1 ring-ainchors-grey-light/20">
                            <a href="{{ url('/trainers-profile') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Trainer Profiles</a>
                            <a href="{{ url('/testimonials') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Testimonials</a>
                            <a href="{{ route('courses.index') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Courses</a>
                            <a href="{{ url('/success-story-of-angie') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Success Story</a>
                        </div>
                    </div>
                </div>

                <div
                    class="relative"
                    @mouseenter="consultingOpen = true"
                    @mouseleave="consultingOpen = false"
                    @focusin="consultingOpen = true"
                    @focusout="consultingOpen = false"
                >
                    <button type="button" class="flex items-center gap-1 text-ainchors-navy transition hover:text-ainchors-green" :aria-expanded="consultingOpen.toString()">
                        Consulting
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-cloak x-show="consultingOpen" x-transition.origin.top.left class="absolute left-0 top-full min-w-[230px] pt-2">
                        <div class="rounded-ainchors-card bg-ainchors-white py-2 shadow-lg ring-1 ring-ainchors-grey-light/20">
                            <a href="{{ url('/consulting-gov') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Public/Government Sector</a>
                            <a href="{{ url('/consulting-private') }}" class="block px-4 py-2 text-ainchors-navy transition hover:bg-ainchors-green-hero hover:text-ainchors-green">Private Sector</a>
                        </div>
                    </div>
                </div>

                <a href="{{ url('/faqs') }}" class="text-ainchors-navy transition hover:text-ainchors-green">FAQ's</a>
                <a href="{{ url('/hiring-page') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Join Us</a>
            </nav>

            <div class="hidden flex-shrink-0 items-center gap-4 lg:flex">
                @auth
                    <a href="{{ route('my-courses') }}" class="font-sans text-ainchors-body text-ainchors-navy transition hover:text-ainchors-green">My Courses</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-sans text-ainchors-body text-ainchors-navy transition hover:text-ainchors-green">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="font-sans text-ainchors-body text-ainchors-navy transition hover:text-ainchors-green">Login</a>
                    <x-button variant="primary" :href="url('/contact-us')">Contact us</x-button>
                @endauth
            </div>

            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                class="text-ainchors-navy lg:hidden"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="mobile-navigation"
                aria-label="Toggle navigation"
            >
                <svg x-show="!mobileOpen" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <svg x-cloak x-show="mobileOpen" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <nav
        id="mobile-navigation"
        x-cloak
        x-show="mobileOpen"
        x-transition.origin.top
        aria-label="Mobile navigation"
        class="max-h-[calc(100vh-5rem)] overflow-y-auto border-t border-ainchors-grey-light/20 bg-ainchors-white px-6 py-4 font-sans text-ainchors-body lg:hidden"
    >
        <div class="mx-auto flex max-w-ainchors-container flex-col gap-3">
            <a href="{{ route('home') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Home</a>
            <a href="{{ url('/about-us-814253') }}" class="text-ainchors-navy transition hover:text-ainchors-green">About us</a>
            <a href="{{ url('/trainers-profile') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Training</a>
            <a href="{{ url('/consulting-main') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Consulting</a>
            <a href="{{ url('/faqs') }}" class="text-ainchors-navy transition hover:text-ainchors-green">FAQ's</a>
            <a href="{{ url('/hiring-page') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Join Us</a>
            <a href="{{ url('/contact-us') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Contact us</a>
            @auth
                <a href="{{ route('my-courses') }}" class="text-ainchors-navy transition hover:text-ainchors-green">My Courses</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-ainchors-navy transition hover:text-ainchors-green">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-ainchors-navy transition hover:text-ainchors-green">Login</a>
            @endauth
        </div>
    </nav>
</header>
