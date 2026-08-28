@extends('layouts.app')

@section('title', "Founder's Background | AINCHORS")

@section('content')
    <article id="angie-foong-top" class="bg-white font-sans text-[#202124]">
        <header class="bg-gradient-to-r from-[#f8fbfd] via-[#effcff] to-[#e8f8ff] px-6 py-10 sm:py-12">
            <div class="mx-auto max-w-[1180px]">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Founder's Background</h1>
                <p class="mt-4 max-w-[1120px] text-sm leading-6 text-[#4e585d] sm:text-base">
                    This page gives a concise overview of our founder's background, journey, and values, so you can understand the story and purpose behind AINCHORS. You'll get context on why the company was created, what's its motive, and how it shapes the programmes we offer.
                </p>
            </div>
        </header>

        <section class="px-6 py-12 sm:py-16">
            <div class="mx-auto grid max-w-[1120px] items-start gap-10 lg:grid-cols-[300px_1fr] lg:gap-16">
                <img src="{{ asset('assets/site/6981a1fc0e14662e2af15e2f.jpg') }}" alt="Angie Foong" class="mx-auto h-auto w-full max-w-[300px] border border-black object-cover">

                <div>
                    <h2 class="text-4xl font-bold tracking-tight">Angie Foong</h2>
                    <ul class="mt-7 space-y-3 text-base font-semibold">
                        @foreach ([
                            'HRD Corp Certified Trainer',
                            'Neuro‑Linguistic Programming (NLP) Certified',
                            'CPA Australia',
                            'B. Commerce (Accounting And Corp Finance - Australia)',
                            'Specialized in AI & Fintech',
                        ] as $credential)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#1877f2] text-[10px] font-bold text-white" aria-hidden="true">✓</span>
                                <span>{{ $credential }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 space-y-5 text-base leading-7 text-[#30363a]">
                        <p>Angie Foong is a Certified Trainer and the Founder of <strong>AINCHORS</strong> Solutions (6 years), a learning and consultancy firm that provide training and advisory services to clients relating to AI, Core banking system and operations, Cross border payment processing, E-payment, Cards, Data Management, Blockchain Technology, Tokenization in Banking, Central Bank Digital Currency (CBDC), Metaverse and Decentralised Finance (DeFi), Leadership Courses in the banking industry.</p>
                        <p>CPA professional with 18 years of international working experience within banking (Citibank, HSBC, Standard Chartered), accounting firm (PwC) and engineering/construction industries before venturing into training and consulting industry.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-6 pb-12 sm:pb-16">
            <div class="mx-auto max-w-[1180px] border-t border-black pt-10">
                <p class="mx-auto max-w-[1080px] text-center text-base leading-7 text-[#30363a] sm:text-lg">
                    One of the defining moments of my early life was being recognised and awarded for the sports woman of the year on stage by Former Malaysian Prime Minister Mahathir Mohamad's wife, <strong>Tun Dr. Siti Hasmah Mohamad Ali</strong> and <strong>Dato' Seri Dr. Wan Azizah Wan Ismail</strong>, the wife of Malaysian Prime Minister Anwar Ibrahim. It reinforced a simple truth: when you consistently perform at a high level, people notice—and more importantly, they follow.
                </p>

                @php
                    $awardImages = [
                        asset('assets/site/69e1b6d12c135a8c83ba642c.jpeg'),
                        asset('assets/site/69e1b6d150b9a3263a789b98.jpeg'),
                    ];
                @endphp
                <div class="mx-auto mt-10 max-w-[1080px]" data-founder-carousel>
                    <div class="relative overflow-hidden bg-[#f4f4f4]">
                        <div class="flex will-change-transform" data-carousel-track style="transform:translateX(-100%);transition:transform 400ms ease-in-out">
                            <div class="w-full shrink-0" aria-hidden="true"><img src="{{ $awardImages[array_key_last($awardImages)] }}" alt="" class="aspect-[16/10] w-full object-cover"></div>
                            @foreach ($awardImages as $image)
                                <div class="w-full shrink-0"><img src="{{ $image }}" alt="Angie Foong receiving a sports award" class="aspect-[16/10] w-full object-cover"></div>
                            @endforeach
                            <div class="w-full shrink-0" aria-hidden="true"><img src="{{ $awardImages[0] }}" alt="" class="aspect-[16/10] w-full object-cover"></div>
                        </div>
                        <button type="button" data-carousel-previous class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white" aria-label="Previous image">‹</button>
                        <button type="button" data-carousel-next class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white" aria-label="Next image">›</button>
                    </div>
                    <div class="mt-4 flex justify-center gap-2" aria-label="Award photo selection">
                        @foreach ($awardImages as $image)
                            <button type="button" data-carousel-dot="{{ $loop->index }}" class="h-3 w-3 rounded-full border border-[#37ad82] {{ $loop->first ? 'bg-[#37ad82]' : 'bg-white' }}" aria-label="Show award image {{ $loop->iteration }}" @if ($loop->first) aria-current="true" @endif></button>
                        @endforeach
                    </div>
                </div>

                <p class="mx-auto mt-5 max-w-[1080px] text-base leading-7 text-[#4e585d]">From the edge of a school swimming pool to building AI‑powered businesses, my journey has always been shaped by high performance, discipline, and leadership.</p>
            </div>
        </section>

        <section class="px-6 pb-14 sm:pb-20">
            @php
                $sportsImages = [
                    asset('assets/site/69e1b6d138381eafa8f4d5a8.jpeg'),
                    asset('assets/site/69e1b6d138381eafa8f4d5aa.jpeg'),
                    asset('assets/site/69e1b6d150b9a3263a789b97.jpeg'),
                    asset('assets/site/69e1b6d12c135a8c83ba642a.jpeg'),
                    asset('assets/site/69e1b6d12c135a8c83ba6429.jpeg'),
                ];
            @endphp
            <div class="mx-auto max-w-[920px]" data-founder-carousel>
                <div class="relative overflow-hidden bg-[#f7f7f7]">
                    <div class="flex will-change-transform" data-carousel-track style="transform:translateX(-100%);transition:transform 400ms ease-in-out">
                        <div class="w-full shrink-0" aria-hidden="true"><img src="{{ $sportsImages[array_key_last($sportsImages)] }}" alt="" class="aspect-[16/10] w-full object-contain"></div>
                        @foreach ($sportsImages as $image)
                            <div class="w-full shrink-0"><img src="{{ $image }}" alt="Angie Foong's school sports and leadership journey" class="aspect-[16/10] w-full object-contain"></div>
                        @endforeach
                        <div class="w-full shrink-0" aria-hidden="true"><img src="{{ $sportsImages[0] }}" alt="" class="aspect-[16/10] w-full object-contain"></div>
                    </div>
                    <button type="button" data-carousel-previous class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white" aria-label="Previous image">‹</button>
                    <button type="button" data-carousel-next class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white" aria-label="Next image">›</button>
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-2" aria-label="Sports photo selection">
                    @foreach ($sportsImages as $image)
                        <button type="button" data-carousel-dot="{{ $loop->index }}" class="h-3 w-3 rounded-full border border-[#37ad82] {{ $loop->first ? 'bg-[#37ad82]' : 'bg-white' }}" aria-label="Show sports image {{ $loop->iteration }}" @if ($loop->first) aria-current="true" @endif></button>
                    @endforeach
                </div>
            </div>

            <div class="mx-auto mt-10 max-w-[980px] space-y-6 text-base leading-8 text-[#30363a] sm:text-lg">
                <p>I grew up in lanes, not meeting rooms. As a young competitive swimmer representing my state, I learned early that results are built in silence. Long training sessions, counting strokes, holding form when my muscles burned and nobody was watching—this was where performance was forged. The pool became my first classroom in consistency and data‑driven improvement. Every lap, every split time, every personal best showed me exactly where I stood—and what I had to improve.</p>
                <p>Sports didn’t stop at the water. On the track, representing my state in running, I learned a different kind of endurance. When your lungs are burning and the finish line still feels far away, you don’t slow down—you adjust, you push, and you finish strong. That mindset taught me that when pressure builds, you have to stay composed, you have to pace strategically, and you have to deliver when it matters most. Today, I apply the same principles to scaling teams, products, and revenue.</p>
                <p>Leadership came early and with it, responsibility. As the Sports Captain of my school, I understood quickly that when you lead, you don’t get to choose when to perform you have to show up every day, you have to set the standard, and you have to carry the team forward, especially when it’s hardest. I was responsible not just for results, but for discipline, focus, and morale. I learned how to rally a team after setbacks, steady nerves before critical moments, and maintain high standards without burning people out.</p>
                <p>Beyond performance, leadership meant service and accountability. As a board of directors member in the Leo Club, I organised initiatives and carried out charity work for underprivileged communities. It taught me that leadership is not optional impact you have to serve, you have to contribute, and you have to think beyond yourself.</p>
                <p>At the same time, as a disciplinary officer in the prefectorial board, I was entrusted with upholding order and ensuring compliance in school. That role required more than authority it demanded consistency. You have to enforce standards fairly, you have to lead with integrity, and you have to earn respect through your actions, not your title.</p>
                <p>School became my first real leadership lab. Through uniformed bodies and student organisations, I organised events, managed teams, and represented my peers. Every responsibility I took on reinforced one belief: talent alone is never enough you have to build structure, you have to maintain discipline, and you have to execute consistently.</p>
            </div>
        </section>

        <section class="bg-[#f7fbfc] px-6 py-14 sm:py-20">
            <div class="mx-auto grid max-w-[1120px] items-center gap-10 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-14">
                <div class="space-y-6 text-base leading-8 text-[#30363a] sm:text-lg">
                    <p>As I moved into professional life, these principles became my edge. High-pressure presentations, client engagements, and strategic decisions felt familiar. I had already been trained to perform when it counts. The only difference was the scoreboard—no longer medals, but measurable business outcomes, long-term partnerships, and scalable growth.</p>
                    <h2 class="text-3xl font-bold leading-tight text-[#2e3341] sm:text-4xl">Today, I bring that same high-performance mindset into AINCHORS.</h2>
                    <p>AINCHORS exists to help organisations adopt, implement, and monetise AI in ways that directly impact revenue and efficiency. I don’t believe in shortcuts. In business, just like in sport, you have to build systems, you have to rely on data, and you have to commit to continuous improvement if you want consistent results.</p>
                </div>
                <img src="{{ asset('assets/site/6a33dc7f6dd61c546a22c720.jpg') }}" alt="Angie Foong speaking at a professional event" class="mx-auto w-full max-w-[380px] object-cover">
            </div>

            <div class="mx-auto mt-12 max-w-[980px] space-y-6 text-base leading-8 text-[#30363a] sm:text-lg">
                <p>I build teams the same way winning teams are built—placing the right people in the right roles, supported by the right systems, at the right time. Because growth is never accidental—it is structured, intentional, and executed.</p>
                <p>This journey was never a sudden leap. It was a progression. The athlete who trusted the process became the Sports Captain who led under pressure, the student leader who served and upheld discipline, and now the founder who builds AI-driven systems that perform, adapt, and scale.</p>
                <p>As the founder of AINCHORS, I don’t just teach AI—I build performance. I bring discipline, structure, and execution into every client engagement.</p>
                <p>Because at the end of the day, success is not about trying harder.</p>
                <p>It’s about knowing that if you want to win you have to do the work others avoid, you have to stay consistent when results are invisible, and you have to execute at a level most people are not willing to sustain.</p>
                <p>That is how AINCHORS helps businesses turn AI into predictable revenue, operational efficiency, and long term competitive advantage on the path to success and far beyond.</p>
                <p class="pt-4 text-center"><button type="button" data-founder-back-to-top class="inline-flex rounded-full bg-[#37ad82] px-7 py-3 font-semibold text-white transition hover:bg-[#2e9470] focus:outline-none focus:ring-2 focus:ring-[#37ad82] focus:ring-offset-2">Back To Top</button></p>
            </div>
        </section>
    </article>

    <script>
        (function () {
            document.querySelectorAll('[data-founder-carousel]').forEach(function (carousel) {
                const track = carousel.querySelector('[data-carousel-track]');
                const dots = Array.from(carousel.querySelectorAll('[data-carousel-dot]'));
                const realSlideCount = dots.length;
                let index = 1;
                let moving = false;

                const updateDots = function () {
                    const active = (index - 1 + realSlideCount) % realSlideCount;
                    dots.forEach(function (dot, dotIndex) {
                        const isActive = dotIndex === active;
                        dot.classList.toggle('bg-[#37ad82]', isActive);
                        dot.classList.toggle('bg-white', !isActive);
                        if (isActive) {
                            dot.setAttribute('aria-current', 'true');
                        } else {
                            dot.removeAttribute('aria-current');
                        }
                    });
                };

                const moveTo = function (nextIndex, animate) {
                    index = nextIndex;
                    track.style.transition = animate ? 'transform 400ms ease-in-out' : 'none';
                    track.style.transform = `translateX(-${index * 100}%)`;
                    updateDots();
                };

                const moveBy = function (direction) {
                    if (moving) return;
                    moving = true;
                    moveTo(index + direction, true);
                };

                carousel.querySelector('[data-carousel-next]').addEventListener('click', function () {
                    moveBy(1);
                });

                carousel.querySelector('[data-carousel-previous]').addEventListener('click', function () {
                    moveBy(-1);
                });

                dots.forEach(function (dot, dotIndex) {
                    dot.addEventListener('click', function () {
                        if (moving || index === dotIndex + 1) return;
                        moving = true;
                        moveTo(dotIndex + 1, true);
                    });
                });

                track.addEventListener('transitionend', function (event) {
                    if (event.propertyName !== 'transform') return;

                    if (index === 0) {
                        moveTo(realSlideCount, false);
                    } else if (index === realSlideCount + 1) {
                        moveTo(1, false);
                    }

                    moving = false;
                });
            });

            document.querySelector('[data-founder-back-to-top]').addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
@endsection
