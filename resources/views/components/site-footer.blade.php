<footer class="bg-ainchors-navy py-16 text-ainchors-white">
    <div class="mx-auto max-w-ainchors-container px-6">
        <div class="mb-12 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <img src="{{ asset('assets/footer-logo.webp') }}" alt="AINCHORS Training & Consulting" class="mb-4 h-14 w-auto">
                <p class="font-sans text-sm text-ainchors-grey-light">Anchoring The Future</p>
            </div>

            <div>
                <h5 class="mb-4 font-sans font-bold text-ainchors-white">Explore Site</h5>
                <ul class="space-y-2 font-sans text-ainchors-body text-ainchors-grey-light">
                    <li><a href="{{ url('/about-us-814253') }}" class="transition hover:text-ainchors-green">About us</a></li>
                    <li><a href="{{ route('courses.index') }}" class="transition hover:text-ainchors-green">Courses</a></li>
                    <li><a href="{{ url('/testimonials') }}" class="transition hover:text-ainchors-green">Testimonials</a></li>
                    <li><a href="{{ url('/faqs') }}" class="transition hover:text-ainchors-green">FAQ's</a></li>
                    <li><a href="{{ url('/events') }}" class="transition hover:text-ainchors-green">Events</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 font-sans font-bold text-ainchors-white">Useful Links</h5>
                <ul class="space-y-2 font-sans text-ainchors-body text-ainchors-grey-light">
                    <li><a href="{{ url('/contact-us') }}" class="transition hover:text-ainchors-green">Contact us</a></li>
                    <li><a href="{{ url('/terms--conditions') }}" class="transition hover:text-ainchors-green">Terms &amp; Conditions</a></li>
                    <li><a href="{{ url('/privacy--policy') }}" class="transition hover:text-ainchors-green">Privacy Policy</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 font-sans font-bold text-ainchors-white">Locations</h5>
                <p class="mb-1 font-sans text-ainchors-body font-semibold">Australia:</p>
                <div class="space-y-1 font-sans text-sm leading-relaxed text-ainchors-grey-light">
                    <p>AI Anchor Solutions Pty Ltd</p>
                    <p>ACN No: 691339714</p>
                    <p>ABN No: 99691339714</p>
                    <p>Address: U803 5 Waterways Street Wentworth Point NSW 2127 Australia</p>
                </div>

                <p class="mb-1 mt-5 font-sans text-ainchors-body font-semibold">Malaysia:</p>
                <div class="space-y-1 font-sans text-sm leading-relaxed text-ainchors-grey-light">
                    <p>AINCHORS Sdn Bhd</p>
                    <p>(Formerly registered as Anchors Solution Sdn Bhd)</p>
                    <p>202001021528 (1377848K)</p>
                    <p>Tel: +60167022788</p>
                    <p>Address: Level 13A, Wisma Mont Kiara, 1, Jalan Kiara, Mont Kiara, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-10 border-t border-ainchors-grey-dark/40 pt-10 md:grid-cols-2">
            <div>
                <p class="mb-2 font-sans text-ainchors-body text-ainchors-grey-light">
                    Email: <a href="mailto:info@ainchors.com" class="transition hover:text-ainchors-green">info@ainchors.com</a>
                </p>
                <p class="mb-4 font-sans text-ainchors-body text-ainchors-grey-light">
                    WhatsApp: <a href="https://wa.me/+61418802086" target="_blank" rel="noopener noreferrer" class="transition hover:text-ainchors-green">+61 418 802 086</a>
                </p>

                <div class="flex flex-wrap items-center gap-4">
                    @foreach ([
                        ['facebook', 'Facebook', 'https://facebook.com'],
                        ['instagram', 'Instagram', 'https://www.instagram.com/ainchors.training.consulting/'],
                        ['linkedin', 'LinkedIn', 'https://linkedin.com'],
                        ['tiktok', 'TikTok', 'https://tiktok.com'],
                        ['whatsapp', 'WhatsApp', 'https://wa.me/+61418802086'],
                    ] as [$icon, $label, $href])
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}" class="grid h-8 w-8 place-items-center rounded transition hover:bg-ainchors-green">
                            <img src="{{ asset('assets/'.$icon.'.svg') }}" alt="" class="h-5 w-5">
                        </a>
                    @endforeach
                    <a href="mailto:info@ainchors.com" aria-label="Email" class="grid h-8 w-8 place-items-center rounded transition hover:bg-ainchors-green">
                        <img src="{{ asset('assets/mail.svg') }}" alt="" class="h-5 w-5">
                    </a>
                </div>
            </div>

            <div x-data="{ submitted: false }">
                <h5 class="mb-1 font-sans font-bold text-ainchors-white">Begin Your Journey Today!</h5>
                <p class="mb-4 font-sans text-sm text-ainchors-grey-light">Contact Us!</p>
                <form @submit.prevent="submitted = true" class="space-y-3">
                    <label class="sr-only" for="footer-full-name">Full Name</label>
                    <input id="footer-full-name" type="text" name="full_name" placeholder="Full Name" required class="w-full rounded-ainchors-button bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy">
                    <label class="sr-only" for="footer-email">Email</label>
                    <input id="footer-email" type="email" name="email" placeholder="Email*" required class="w-full rounded-ainchors-button bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy">
                    <label class="sr-only" for="footer-phone">Phone</label>
                    <input id="footer-phone" type="tel" name="phone" placeholder="Phone*" required class="w-full rounded-ainchors-button bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy">
                    <label class="sr-only" for="footer-country">Country</label>
                    <select id="footer-country" name="country" required class="w-full rounded-ainchors-button bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy">
                        <option value="">Country</option>
                        <option value="AU">Australia</option>
                        <option value="MY">Malaysia</option>
                        <option value="OTHER">Other</option>
                    </select>
                    <x-button variant="primary" type="submit">Submit</x-button>
                    <p x-cloak x-show="submitted" role="status" class="font-sans text-sm text-ainchors-green">Thank you.</p>
                </form>
            </div>
        </div>

        <p class="mt-10 text-center font-sans text-xs text-ainchors-grey-light">
            Copyright {{ date('Y') }}. All Rights Reserved. AINCHORS Training &amp; Consulting
        </p>
    </div>
</footer>

<div x-data="{ open: false }" class="fixed bottom-6 right-6 z-50">
    <div x-cloak x-show="open" x-transition.origin.bottom.right class="mb-3 w-80 max-w-[calc(100vw-3rem)] rounded-ainchors-card border border-ainchors-grey-light/20 bg-ainchors-white p-4 shadow-xl">
        <p class="mb-2 font-sans font-bold text-ainchors-navy">AINCHORS AI Assistant</p>
        <p class="font-sans text-sm text-ainchors-grey-dark">Hi 👋 How can I help you today?</p>
    </div>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-label="Toggle AINCHORS AI Assistant"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-ainchors-green text-ainchors-white shadow-lg transition hover:scale-105"
    >
        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8-1.2 0-2.4-.2-3.4-.6L3 20l1.3-3.9C3.5 14.9 3 13.5 3 12c0-4.4 4-8 9-8s9 3.6 9 8z"></path></svg>
        <svg x-cloak x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
</div>
