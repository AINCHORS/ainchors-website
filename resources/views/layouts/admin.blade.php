<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AINCHORS administration">
    <title>@yield('title', 'Admin | AINCHORS')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ navigationOpen: false }" class="min-h-screen overflow-x-hidden bg-[#f2f7f5] font-sans text-ainchors-navy antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>

    @php
        $navigationSections = [
            [
                'label' => 'Management',
                'items' => [
                    ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
                    ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'Users', 'icon' => 'users'],
                    ['route' => 'admin.products.index', 'active' => ['admin.products.*', 'admin.package-members.*'], 'label' => 'Products', 'icon' => 'products'],
                    ['route' => 'admin.course-content.index', 'active' => 'admin.course-content.*', 'label' => 'Course content', 'icon' => 'products'],
                    ['route' => 'admin.enrollments.index', 'active' => 'admin.enrollments.*', 'label' => 'Enrollments', 'icon' => 'enrollments'],
                ],
            ],
            [
                'label' => 'Commerce',
                'items' => [
                    ['route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'label' => 'Orders', 'icon' => 'orders'],
                    ['route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'label' => 'Payments', 'icon' => 'payments'],
                ],
            ],
            [
                'label' => 'CRM',
                'items' => [
                    ['route' => 'admin.leads.index', 'active' => 'admin.leads.*', 'label' => 'Contact submissions', 'icon' => 'leads'],
                    ['route' => 'admin.consultations.index', 'active' => 'admin.consultations.*', 'label' => 'Consultations', 'icon' => 'consultations'],
                ],
            ],
            [
                'label' => 'Careers',
                'items' => [
                    ['route' => 'admin.job-applications.index', 'active' => 'admin.job-applications.*', 'label' => 'Job applications', 'icon' => 'users'],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['route' => 'admin.audit-log.index', 'active' => 'admin.audit-log.*', 'label' => 'Audit log', 'icon' => 'audit'],
                    ['route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'label' => 'Settings', 'icon' => 'settings'],
                ],
            ],
        ];
        $utilityNavigation = [
            ['label' => 'Analytics', 'icon' => 'analytics'],
            ['label' => 'SEO analysis', 'icon' => 'seo'],
        ];
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)]">
        <aside class="hidden min-h-screen border-r border-ainchors-green/20 bg-[#123f3a] text-white lg:sticky lg:top-0 lg:flex lg:max-h-screen lg:flex-col">
            <div class="border-b border-white/10 px-5 py-5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-ainchors-button bg-white p-3 shadow-sm ring-1 ring-white/20 focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2 focus:ring-offset-[#123f3a]">
                    <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS" class="h-auto min-w-0 flex-1 object-contain">
                    <span class="shrink-0 border-l border-ainchors-navy/15 pl-3 text-[0.6875rem] font-bold uppercase tracking-[0.16em] text-[#123f3a]">Admin</span>
                </a>
            </div>

            <nav aria-label="Admin navigation" class="flex-1 overflow-y-auto px-3 py-5">
                @foreach ($navigationSections as $section)
                    <div @class(['mt-6 border-t border-white/10 pt-5' => ! $loop->first])>
                        <p class="px-3 pb-2 text-[0.6875rem] font-bold uppercase tracking-[0.16em] text-white/45">{{ $section['label'] }}</p>
                        <div class="space-y-1">
                            @foreach ($section['items'] as $item)
                                @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                    <a href="{{ route($item['route']) }}" @class([
                                        'flex items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2 focus:ring-offset-[#123f3a]',
                                        'bg-ainchors-green text-white shadow-sm' => request()->routeIs($item['active']),
                                        'text-white/75 hover:bg-ainchors-green/15 hover:text-white' => ! request()->routeIs($item['active']),
                                    ])>
                                        @include('admin.partials.navigation-icon', ['icon' => $item['icon']])
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="mt-7 border-t border-white/10 pt-5">
                    <p class="px-3 pb-2 text-[0.6875rem] font-bold uppercase tracking-[0.16em] text-white/45">Later phases</p>
                    <div class="space-y-1">
                        @foreach ($utilityNavigation as $item)
                            <span aria-disabled="true" title="Available in a future phase" class="flex cursor-not-allowed items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-sm font-semibold text-white/35">
                                @include('admin.partials.navigation-icon', ['icon' => $item['icon']])
                                <span>{{ $item['label'] }}</span>
                                <span class="ml-auto rounded-full border border-white/15 px-1.5 py-0.5 text-[0.5625rem] font-bold uppercase tracking-[0.1em]">Later</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </nav>

            <div class="border-t border-white/10 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-left text-sm font-semibold text-white/75 transition hover:bg-ainchors-green/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2 focus:ring-offset-[#123f3a]">
                        @include('admin.partials.navigation-icon', ['icon' => 'logout'])
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-40 border-b border-ainchors-green/20 bg-white/95 backdrop-blur lg:hidden">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-ainchors-button focus:outline-none focus:ring-2 focus:ring-ainchors-green">
                        <img src="{{ asset('assets/logo.webp') }}" alt="AINCHORS" class="h-8 w-auto max-w-[8rem] object-contain">
                        <span class="border-l border-ainchors-navy/15 pl-2 text-xs font-bold uppercase tracking-[0.14em] text-ainchors-navy">Admin</span>
                    </a>
                    <button type="button" @click="navigationOpen = ! navigationOpen" :aria-expanded="navigationOpen.toString()" aria-controls="admin-mobile-navigation" class="inline-flex h-10 w-10 items-center justify-center rounded-ainchors-button border border-ainchors-navy/15 bg-white text-ainchors-navy transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">
                        <span class="sr-only">Toggle admin navigation</span>
                        <svg x-show="!navigationOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
                        <svg x-cloak x-show="navigationOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <nav id="admin-mobile-navigation" x-cloak x-show="navigationOpen" x-transition.origin.top class="max-h-[75vh] overflow-y-auto border-t border-ainchors-navy/10 bg-white px-4 py-3 shadow-lg" aria-label="Admin navigation">
                    @foreach ($navigationSections as $section)
                        <div @class(['mt-3 border-t border-ainchors-navy/10 pt-3' => ! $loop->first])>
                            <p class="px-3 pb-1 text-[0.625rem] font-bold uppercase tracking-[0.14em] text-ainchors-grey-light">{{ $section['label'] }}</p>
                            <div class="grid gap-1 sm:grid-cols-2">
                                @foreach ($section['items'] as $item)
                                    @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                        <a href="{{ route($item['route']) }}" @click="navigationOpen = false" @class([
                                            'flex items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-ainchors-green',
                                            'bg-ainchors-green text-white' => request()->routeIs($item['active']),
                                            'text-ainchors-grey-dark hover:bg-ainchors-green-hero hover:text-ainchors-navy' => ! request()->routeIs($item['active']),
                                        ])>
                                            @include('admin.partials.navigation-icon', ['icon' => $item['icon']])
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-3 grid gap-1 border-t border-ainchors-navy/10 pt-3 sm:grid-cols-2">
                        <span aria-disabled="true" class="flex items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-sm font-semibold text-ainchors-grey-light">@include('admin.partials.navigation-icon', ['icon' => 'analytics']) Analytics <span class="ml-auto text-[0.625rem] uppercase tracking-[0.1em]">Later</span></span>
                        <span aria-disabled="true" class="flex items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-sm font-semibold text-ainchors-grey-light">@include('admin.partials.navigation-icon', ['icon' => 'seo']) SEO analysis <span class="ml-auto text-[0.625rem] uppercase tracking-[0.1em]">Later</span></span>
                        <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex w-full items-center gap-3 rounded-ainchors-button px-3 py-2.5 text-left text-sm font-semibold text-ainchors-grey-dark hover:bg-slate-100 hover:text-ainchors-navy">@include('admin.partials.navigation-icon', ['icon' => 'logout']) Log out</button></form>
                    </div>
                </nav>
            </header>

            <main id="main-content" class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10">
                @include('admin.partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
