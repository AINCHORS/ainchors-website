<footer class="bg-[#252525] text-white">
    <div class="mx-auto max-w-ainchors-container px-5 py-7 sm:px-6 sm:py-8">

        <div class="grid items-start gap-7 lg:grid-cols-12 lg:gap-8">

            {{-- =====================================================
                LEFT
                LOGO + LINKS + SOCIAL
            ====================================================== --}}
            <section class="lg:col-span-4">

                {{-- Transparent Footer Logo --}}
                <a
                    href="{{ route('home') }}"
                    class="inline-block"
                    aria-label="AINCHORS Home"
                >
                    <img
                        src="{{ asset('assets/footer-logo.webp') }}"
                        alt="AINCHORS Training & Consulting"
                        class="h-auto w-[285px] max-w-full object-contain sm:w-[300px] lg:w-[315px]"
                    >
                </a>

                {{-- Navigation under logo --}}
                <div class="mt-4 grid grid-cols-2 gap-7">

                    {{-- Explore Site --}}
                    <div>
                        <h2 class="mb-3 font-heading text-xl font-bold leading-tight text-white">
                            Explore Site
                        </h2>

                        <ul class="space-y-1.5 font-sans text-sm leading-5 text-white">

                            <li>
                                <a
                                    href="{{ route('about') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    About Us
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('courses.index') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Courses
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('testimonials') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Testimonials
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('faqs') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    FAQ's
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('events') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Events
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('hiring') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Join Us
                                </a>
                            </li>

                        </ul>
                    </div>


                    {{-- Useful Links --}}
                    <div>
                        <h2 class="mb-3 font-heading text-xl font-bold leading-tight text-white">
                            Useful Links
                        </h2>

                        <ul class="space-y-1.5 font-sans text-sm leading-5 text-white">

                            <li>
                                <a
                                    href="{{ route('contact') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Contact Us
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('terms') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Terms &amp; Conditions
                                </a>
                            </li>

                            <li>
                                <a
                                    href="{{ route('privacy') }}"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    Privacy Policy
                                </a>
                            </li>

                        </ul>
                    </div>

                </div>


                {{-- Social Media --}}
                <div
                    class="mt-5 flex flex-wrap items-center gap-3"
                    aria-label="AINCHORS social media"
                >

                    {{-- Facebook --}}
                    <a
                        href="https://www.facebook.com/people/Ainchors-Training-and-Consulting/61578543300564/"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Facebook"
                        class="grid h-10 w-10 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/facebook.svg') }}"
                            alt=""
                            class="h-7 w-7 object-contain"
                        >
                    </a>

                    {{-- Instagram --}}
                    <a
                        href="https://www.instagram.com/ainchors.ai.fintech/"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Instagram"
                        class="grid h-10 w-10 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/instagram.svg') }}"
                            alt=""
                            class="h-7 w-7 object-contain"
                        >
                    </a>

                    {{-- LinkedIn --}}
                    <a
                        href="https://linkedin.com/company/ainchors"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="LinkedIn"
                        class="grid h-10 w-10 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/linkedin.svg') }}"
                            alt=""
                            class="h-7 w-7 object-contain"
                        >
                    </a>

                    {{-- TikTok --}}
                    <a
                        href="https://tiktok.com/@ainchors.ai.fintech"
                         {{-- TikTok latest link(don't change) --}}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="TikTok"
                        class="grid h-10 w-10 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/tiktok.svg') }}"
                            alt=""
                            class="h-7 w-7 object-contain"
                        >
                    </a>

                    {{-- YouTube --}}
                    <a
                        href="https://www.youtube.com/@ainchors.ai.fintech"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="YouTube"
                        class="grid h-11 w-11 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/youtube-brand.svg') }}"
                            alt=""
                            class="h-8 w-8 object-contain"
                        >
                    </a>
                    {{-- WhatsApp --}}
                    <a
                        href="https://wa.me/+61418802086"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="WhatsApp"
                        class="grid h-10 w-10 place-items-center transition hover:scale-110"
                    >
                        <img
                            src="{{ asset('assets/whatsapp.svg') }}"
                            alt=""
                            class="h-7 w-7 object-contain"
                        >
                    </a>

                    {{-- Email --}}
                    <a
                        href="https://mail.google.com/mail/?view=cm&fs=1&to=info@ainchors.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Email AINCHORS"
                        class="grid h-10 w-10 place-items-center text-white transition hover:scale-110 hover:text-ainchors-green"
                    >
                        <svg
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 6.75A2.75 2.75 0 0 1 5.75 4h12.5A2.75 2.75 0 0 1 21 6.75v10.5A2.75 2.75 0 0 1 18.25 20H5.75A2.75 2.75 0 0 1 3 17.25V6.75Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="m4 7 8 6 8-6"
                            />
                        </svg>
                    </a>

                </div>

            </section>


            {{-- =====================================================
                MIDDLE
                FULL LOCATIONS / COMPANY INFORMATION
            ====================================================== --}}
            <section
                class="lg:col-span-5"
                aria-labelledby="footer-locations-title"
            >

                <h2
                    id="footer-locations-title"
                    class="mb-3 font-heading text-2xl font-bold leading-tight text-white"
                >
                    Locations
                </h2>

                <div class="space-y-4 font-sans text-sm leading-5 text-white">

                    {{-- Australia --}}
                    <div>

                        <h3 class="font-bold text-white">
                            Australia:
                        </h3>

                        <div class="mt-1 space-y-0.5 text-white">

                            <p>
                                AI Anchor Solutions Pty Ltd
                            </p>

                            <p>
                                ACN No: 691339714
                            </p>

                            <p>
                                ABN No: 99691339714
                            </p>

                            <p>
                                Address: U803 5 Waterways Street
                                Wentworth Point NSW 2127 Australia
                            </p>

                            <p>
                                Email:
                                <a
                                    href="mailto:info@ainchors.com"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    info@ainchors.com
                                </a>
                            </p>

                            <p>
                                WhatsApp:
                                <a
                                    href="https://wa.me/+61418802086"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-white transition hover:text-ainchors-green"
                                >
                                    +61 418 802 086
                                </a>
                            </p>

                        </div>
                    </div>


                    {{-- Malaysia --}}
                    <div>

                        <h3 class="font-bold text-white">
                            Malaysia:
                        </h3>

                        <div class="mt-1 space-y-0.5 text-white">

                            <p>
                                AINCHORS Sdn Bhd
                            </p>

                            <p>
                                (Formerly registered as Anchors Solution Sdn Bhd)
                            </p>

                            <p>
                                202001021528 (1377848K)
                            </p>

                            <p>
                                Tel: +60167022788
                            </p>

                            <p>
                                Address: Level 13A, Wisma Mont Kiara, 1,
                                Jalan Kiara, Mont Kiara,
                                50480 Kuala Lumpur,
                                Wilayah Persekutuan Kuala Lumpur
                            </p>

                        </div>
                    </div>

                </div>

            </section>


            {{-- =====================================================
                RIGHT
                COMPACT CTA
            ====================================================== --}}
            <section
                class="lg:col-span-3"
                aria-labelledby="footer-cta-title"
            >

                <div
                    class="rounded-ainchors-card
                           border border-white/20
                           bg-white/[0.04]
                           p-5"
                >

                    <h2
                        id="footer-cta-title"
                        class="font-heading text-2xl font-bold leading-tight text-white"
                    >
                        Begin Your Journey Today!
                    </h2>

                    <p
                        class="mt-2 font-sans text-sm leading-5 text-white"
                    >
                        Have a question about our AI solutions,
                        consulting or training?
                    </p>

                    <a
                        href="{{ route('contact') }}"
                        class="mt-4 inline-flex
                               items-center
                               justify-center
                               rounded-ainchors-button
                               bg-ainchors-green
                               px-6 py-2.5
                               font-sans
                               text-sm
                               font-semibold
                               text-white
                               transition
                               hover:bg-ainchors-green/90
                               focus:outline-none
                               focus:ring-2
                               focus:ring-ainchors-green
                               focus:ring-offset-2
                               focus:ring-offset-[#252525]"
                    >
                        Contact Us
                    </a>

                </div>

            </section>

        </div>


        {{-- =====================================================
            COPYRIGHT
        ====================================================== --}}
        <div
            class="mt-6 border-t border-white/20 pt-4"
        >

            <p
                class="text-center font-sans text-xs leading-5 text-white"
            >
                Copyright {{ date('Y') }}.
                All Rights Reserved.
                AINCHORS Training &amp; Consulting
            </p>

        </div>

    </div>
</footer>