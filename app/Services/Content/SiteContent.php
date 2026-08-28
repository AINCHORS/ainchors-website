<?php

namespace App\Services\Content;

class SiteContent
{
    public function home(): array
    {
        return [
            'navigation' => config('ainchors.navigation'),
            'hero' => [
                'heading' => 'Empowering Talent to Shape The Future',
                'body' => 'AINCHORS is a global fintech firm in learning and strategy, delivering high-impact corporate training and strategic business consulting to organizations around the world. The company empowers learners, professionals, and enterprises with practical knowledge and know‑how, designing transformational learning journeys enhanced by advanced digital tools and the power of artificial intelligence to navigate an ever-evolving world.',
            ],
            'clients' => config('ainchors.clients'),
            'services' => [
                [
                    'heading' => 'Corporate training',
                    'body' => 'We offer corporate training to cater for our customer requirements. Kindly contact is if you would like to know more in order for us to customize your needs.',
                    'image' => 'assets/service-corporate.webp',
                    'label' => 'Contact Us!',
                    'url' => 'https://wa.me/+61418802086',
                ],
                [
                    'heading' => 'Self learning Courses',
                    'body' => 'Our self-learning courses offer flexible and independent paths to skill-building. Each course provides concise, practical lessons with hands-on exercises to boost productivity and personal growth at your own pace.​',
                    'image' => 'assets/service-self-learning.webp',
                    'label' => 'Apply Now!',
                    'url' => route('courses.index'),
                ],
                [
                    'heading' => 'Mentorship and Coaching',
                    'body' => 'Our mentorship and coaching courses are designed by experts who have years of experience and proven results in this industry. Feel free to contact us!',
                    'image' => 'assets/service-mentorship.webp',
                    'label' => 'Contact Us!',
                    'url' => 'https://wa.me/+61418802086',
                ],
            ],
            'testimonials' => [
                [
                    'quote' => 'The topic of AI was explained in such a clear and comprehensible way that it made a highly complex subject easy for us to understand.',
                    'avatar' => 'assets/avatar-shamsah.jpg',
                    'name' => 'Shamsah Ibrahim',
                ],
                [
                    'quote' => 'Angie Foong is extremely helpful and always does her best to ensure we understand everything, using plenty of real-time examples to make the concepts even clearer',
                    'avatar' => 'assets/avatar-mouza.jpg',
                    'name' => 'Mouza Alshehhi',
                ],
                [
                    'quote' => 'Angie is an excellent trainer who delivered an engaging and highly informative session, enabling me to gain a clear understanding of what AI truly is and how to harness AI tools to streamline my work, boost productivity, and achieve my goals more efficiently. I am also keen to further explore AI in the context of entrepreneurship as well as its applications in blockchain technology.',
                    'avatar' => 'assets/avatar-ahmed.webp',
                    'name' => 'Ahmed Salem',
                ],
            ],
            'footer' => config('ainchors.footer'),
        ];
    }

    /**
     * Extracted verbatim from resources/legacy/about-us/index.html.
     * Source baseline: docs/LEGACY-NATIVE-CONTENT-BASELINE.md.
     */
    public function about(): array
    {
        return [
            'intro' => 'Discover the story behind our mission and the team that makes it all happen. We’re driven by a passion for serving our customers with honesty, creativity, and care.',
            'story' => [
                'At AINCHORS , we empower institutions, businesses and individuals to navigate the future through the strategic application of Artificial Intelligence (AI), Data Analytics, and Blockchain technologies. As a trusted corporate training and advisory partner, we design and deliver high-impact learning experiences that enable leaders and teams to turn emerging technologies into measurable business outcomes.',
                'International presence across the globe especially in the UAE and active across the GCC, China, and Southeast Asia, Ainchors collaborates with international banks, central banks, and financial regulators to accelerate digital transformation across the financial sector. Our programs are known for blending technical rigor, strategic perspective, and real-world relevance, equipping professionals to integrate innovation within regulatory and operational frameworks.',
                'Our multidisciplinary team of industry practitioners, data scientists, and fintech experts brings decades of experience spanning banking, risk management, digital policy, and business innovation. Whether delivering an executive workshop on AI governance, a deep dive into blockchain for cross-border payments, or a practical AI productivity bootcamp for SMEs, Ainchors ensures each program delivers lasting organizational impact.',
            ],
            'founder_history' => [
                'The inspiration behind founding this company stems from a deep understanding of how Artificial Intelligence (AI) and financial technology (fintech) are fundamentally transforming the world’s economic and professional landscape. Having spent years in the banking industry, I’ve witnessed how technology is redefining traditional systems from payment networks to data-driven decision-making ushering us into a new era where AI enables speed, precision, and inclusion at an unprecedented scale.',
                'Yet, as the world accelerates into this digital evolution, there’s a growing gap between technological advancement and human capability. Skilled, adaptable professionals remain in short supply, and many lack the right training to thrive in an AI-powered future. That gap became the spark for this company’s mission: to cultivate the next generation of talented, AI-literate individuals equipped to lead in fintech, analytics, and emerging technology sectors.',
            ],
            'mission' => [
                'At AINCHORS , our mission is to empower the next generation of AI-driven and fintech-ready talent by providing practical, future-focused education that bridges the gap between rapid technological change and real-world skills.',
                'We design learning experiences that equip individuals with the capabilities to thrive in an era shaped by artificial intelligence, digital finance, and evolving payment technologies, ensuring they can lead, innovate, and create sustainable value in the new economy.',
                'Grounded in strong ESG principles, we are committed to making advanced technology education accessible to all, including underserved and less fortunate communities, so that more people can participate meaningfully in the opportunities created by AI and fintech and, in turn, uplift their lives and their societies.',
            ],
            'who_we_serve' => 'Beyond the enterprise and regulatory sphere, AINCHORS extends its expertise to SMEs and individuals, helping them harness the power of AI to increase productivity, optimize operations, and enhance customer engagement. From automating routine processes to using data-driven insights for smarter decision-making, our training equips today’s businesses for an AI-driven economy.',
            'vision' => [
                'AINCHORS aspires to be the trusted training partner of choice across the GCC, China, and Southeast Asia, building a region-wide community of AI and data-literate professionals who confidently lead the next wave of financial and business innovation.',
                'Our vision goes beyond building skills — it’s about driving purposeful impact. Grounded in ESG principles, we are committed to making quality education accessible, especially to underserved communities. By sharing knowledge widely and ethically, we aim to empower more individuals to participate in — and benefit from — the AI revolution.',
                'Through this mission, we’re not just preparing people for the future; we’re helping to shape it — responsibly, inclusively, and intelligently.',
            ],
            'founder_image' => 'assets/site/6981a1fc0e14662e2af15e2f.jpg',
            'clients' => config('ainchors.clients'),
        ];
    }

    /** Extracted verbatim from resources/legacy/faqs/index.html. */
    public function faqs(): array
    {
        return [
            ['question' => 'What types of training do you offer?', 'answer' => 'We offer a range of corporate training programs, including in‑person workshops, virtual sessions, blended programs, and executive coaching, across topics such as leadership, communication, and AI/digital skills.'],
            ['question' => 'Can the training be customised for our organisation?', 'answer' => 'Yes, we can tailor content, case studies, and delivery formats to your industry, roles, and objectives, including fully bespoke programs designed from your internal use cases.'],
            ['question' => 'How do you determine our training needs?', 'answer' => 'We typically start with a brief needs analysis, which may include stakeholder interviews, skills assessments, and review of your strategic priorities to ensure clear learning objectives.'],
            ['question' => 'What is the typical duration and format of your programs?', 'answer' => 'Programs can range from 90‑minute masterclasses to multi‑day workshops and longer learning journeys over several months, delivered onsite, online, or in a blended format.'],
            ['question' => 'How many participants can join each session?', 'answer' => 'Ideal group size depends on the topic, but most workshops run effectively with 10–25 participants; larger groups can be accommodated with co‑facilitation or webinar-style delivery.'],
            ['question' => 'Who are your facilitators and what experience do they have?', 'answer' => 'Our facilitators are experienced practitioners with corporate and industry backgrounds, specialising in adult learning and facilitation, and regularly delivering programs for corporate clients.'],
            ['question' => 'How do you measure the impact of the training?', 'answer' => 'We use a mix of participant feedback, knowledge or skills checks, and follow‑up application measures aligned to your KPIs, and can provide post‑training reports if required.'],
            ['question' => 'Do you provide post‑training support or follow‑up?', 'answer' => 'Yes, we can offer follow‑up coaching, Q&A clinics, micro‑learning modules, or refresher sessions to reinforce behaviour change and support implementation on the job.'],
            ['question' => 'Where is the training delivered and do you travel?', 'answer' => 'We can deliver onsite at your offices, at external venues, or virtually; travel for in‑house programs is arranged by agreement, including interstate or international delivery.'],
            ['question' => 'What are your fees and what is included?', 'answer' => 'Pricing depends on program scope, customization, duration, and group size, and typically includes design, facilitation, standard materials, and a post‑session feedback summary.'],
        ];
    }

    /** Extracted verbatim from resources/legacy/join-us/index.html. */
    public function careers(): array
    {
        return [
            'heading_prefix' => 'Be Part of Something',
            'heading_highlight' => 'Bigger',
            'heading_suffix' => 'with',
            'company_name' => 'AINCHORS',
            'intro' => 'Join a team that actually values your ideas. Discover open roles, see how we work, and find the place where your skills, ambition, and growth are truly supported—then apply in just a few clicks.',
            'about_heading' => 'A Little About Us',
            'about' => 'At AINCHORS , we empower institutions, businesses and individuals to navigate the future through the strategic application of Artificial Intelligence (AI), Data Analytics, and Blockchain technologies. As a trusted corporate training and advisory partner, we design and deliver high-impact learning experiences that enable leaders and teams to turn emerging technologies into measurable business outcomes.',
        ];
    }

    /** Extracted verbatim from resources/legacy/contact-us/index.html. */
    public function contact(): array
    {
        return [
            'intro' => "Feel free to reach out to us — we'd be happy to assist you with any questions or concerns.",
            'phone' => '+61418802086',
            'email' => 'info@ainchors.com',
            'australia' => 'Australia : U803 5 Waterways Street Wentworth Point NSW 2127 Australia',
            'malaysia' => 'Malaysia : Level 13A, Wisma Mont Kiara, 1, Jalan Kiara, Mont Kiara, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur',
            'feedback_heading' => 'Feedback Form',
            'feedback_intro' => 'We would love to hear your thoughts, suggestions, concerns or problems with anything so we can improve!',
            'feedback_prompt' => 'Your feedback matters to us — please take a moment to share your thoughts!',
        ];
    }
}
