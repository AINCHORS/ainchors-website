<footer class="bg-ainchors-navy py-10 text-ainchors-white sm:py-12">
    <div class="mx-auto max-w-ainchors-container px-5 sm:px-6">
        <div class="grid grid-cols-2 gap-6 sm:gap-8 lg:grid-cols-4 lg:gap-10">
            <div class="col-span-2 lg:col-span-1">
                <img src="{{ asset('assets/footer-logo.webp') }}" alt="AINCHORS Training & Consulting" class="mb-3 h-12 w-auto">
                <p class="font-sans text-sm text-ainchors-grey-light">Anchoring The Future</p>
            </div>

            <div>
                <h2 class="mb-3 font-sans text-sm font-bold text-ainchors-white">Explore</h2>
                <ul class="space-y-2 font-sans text-sm text-ainchors-grey-light">
                    <li><a href="{{ route('about') }}" class="transition hover:text-ainchors-green">About Us</a></li>
                    <li><a href="{{ route('courses.index') }}" class="transition hover:text-ainchors-green">Courses</a></li>
                    <li><a href="{{ route('testimonials') }}" class="transition hover:text-ainchors-green">Testimonials</a></li>
                    <li><a href="{{ route('faqs') }}" class="transition hover:text-ainchors-green">FAQ</a></li>
                    <li><a href="{{ route('hiring') }}" class="transition hover:text-ainchors-green">Join Us</a></li>
                </ul>
            </div>

            <div>
                <h2 class="mb-3 font-sans text-sm font-bold text-ainchors-white">Services</h2>
                <ul class="space-y-2 font-sans text-sm text-ainchors-grey-light">
                    <li><a href="{{ route('consulting.main') }}" class="transition hover:text-ainchors-green">Consulting</a></li>
                    <li><a href="{{ route('consulting.government') }}" class="transition hover:text-ainchors-green">Public / Government Sector</a></li>
                    <li><a href="{{ route('consulting.private') }}" class="transition hover:text-ainchors-green">Private Sector</a></li>
                    <li><a href="{{ route('trainers') }}" class="transition hover:text-ainchors-green">Training</a></li>
                </ul>
            </div>

            <div class="col-span-2 lg:col-span-1">
                <h2 class="mb-3 font-sans text-sm font-bold text-ainchors-white">Contact &amp; Locations</h2>
                <div class="space-y-2 font-sans text-sm leading-relaxed text-ainchors-grey-light">
                    <p><a href="mailto:info@ainchors.com" class="transition hover:text-ainchors-green">info@ainchors.com</a></p>
                    <p><a href="https://wa.me/+61418802086" target="_blank" rel="noopener noreferrer" class="transition hover:text-ainchors-green">WhatsApp: +61 418 802 086</a></p>
                    <p><span class="font-semibold text-ainchors-white">Australia:</span> AI Anchor Solutions Pty Ltd, U803 5 Waterways Street, Wentworth Point NSW 2127, Australia</p>
                    <p><span class="font-semibold text-ainchors-white">Malaysia:</span> AINCHORS Sdn Bhd, Level 13A, Wisma Mont Kiara, 1 Jalan Kiara, 50480 Kuala Lumpur, Malaysia</p>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-4 border-t border-ainchors-grey-dark/40 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
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
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 font-sans text-xs text-ainchors-grey-light">
                <a href="{{ route('terms') }}" class="transition hover:text-ainchors-green">Terms</a>
                <a href="{{ route('privacy') }}" class="transition hover:text-ainchors-green">Privacy</a>
                <span>Copyright {{ date('Y') }}. All Rights Reserved. AINCHORS Training &amp; Consulting</span>
            </div>
        </div>
    </div>
</footer>
