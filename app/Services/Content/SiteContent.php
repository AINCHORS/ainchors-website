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
                    'url' => url('/courses'),
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
                    'avatar' => 'assets/avatar-ahmed.webp',
                    'name' => 'Ahmed',
                ],
                [
                    'quote' => 'Angie Foong is extremely helpful and always does her best to ensure we understand everything, using plenty of real-time examples to make the concepts even clearer',
                    'avatar' => 'assets/avatar-mouza.jpg',
                    'name' => 'Mouza',
                ],
                [
                    'quote' => 'Angie is an excellent trainer who delivered an engaging and highly informative session, enabling me to gain a clear understanding of what AI truly is and how to harness AI tools to streamline my work, boost productivity, and achieve my goals more efficiently. I am also keen to further explore AI in the context of entrepreneurship as well as its applications in blockchain technology.',
                    'avatar' => 'assets/avatar-shamsah.jpg',
                    'name' => 'Shamsah',
                ],
            ],
            'footer' => config('ainchors.footer'),
        ];
    }
}
