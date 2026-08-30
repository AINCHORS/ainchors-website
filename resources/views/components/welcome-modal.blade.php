@php
    $frequency = app(\App\Services\Content\SiteSettings::class)->welcomeModalFrequency();
    $routeName = request()->route()?->getName() ?? '';
    $excluded = auth()->check()
        || in_array($routeName, ['login', 'login.store', 'register', 'register.store', 'checkout.show', 'checkout.store', 'checkout.success', 'checkout.failed'], true)
        || str_starts_with(request()->path(), 'admin')
        || str_starts_with(request()->path(), 'password');
    $seen = session()->has('ainchors.welcome_modal_seen');
    $show = ! $excluded && $frequency !== 'disabled' && ($frequency === 'every_page' || ! $seen);

    if ($show && $frequency === 'session_once') {
        session()->put('ainchors.welcome_modal_seen', true);
    }
@endphp

@if ($show)
    <div
        x-data="{
            open: true,
            close() { this.open = false },
            trap(event) {
                if (!this.open || event.key !== 'Tab') return;
                const nodes = [...this.$refs.dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\'-1\'])')];
                const first = nodes[0], last = nodes[nodes.length - 1];
                if (!first || !last) return;
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }"
        x-init="$nextTick(() => $refs.closeButton.focus())"
        x-show="open"
        x-cloak
        @keydown.escape.window="close()"
        @keydown.window="trap($event)"
        class="fixed inset-0 z-[70] grid place-items-center bg-ainchors-navy/55 p-4"
        role="presentation"
    >
        <div x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="welcome-modal-title" aria-describedby="welcome-modal-copy" class="w-full max-w-md rounded-ainchors-card bg-ainchors-white p-7 shadow-2xl sm:p-9">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="font-sans text-sm font-semibold text-ainchors-green">AINCHORS</p>
                    <h2 id="welcome-modal-title" class="mt-2 font-display text-3xl font-bold text-ainchors-navy">Welcome to AINCHORS</h2>
                </div>
                <button x-ref="closeButton" type="button" @click="close()" aria-label="Continue as guest" class="rounded p-1 text-ainchors-grey-dark transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <p id="welcome-modal-copy" class="mt-5 font-sans leading-relaxed text-ainchors-grey-dark">Explore AI solutions, consulting, professional training and courses.</p>
            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <x-button :href="route('register')" variant="primary">Register</x-button>
                <x-button :href="route('login')" variant="secondary">Login</x-button>
            </div>
            <button type="button" @click="close()" class="mt-5 w-full font-sans text-sm font-semibold text-ainchors-grey-dark underline decoration-ainchors-green underline-offset-4 transition hover:text-ainchors-green">Continue as Guest</button>
        </div>
    </div>
@endif
