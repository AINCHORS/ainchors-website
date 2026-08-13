<?php

return [
    'navigation' => [
        ['label' => 'Home', 'url' => '/home'],
        ['label' => 'About us', 'url' => '/about-us-814253'],
        [
            'label' => 'Training', 'url' => '/trainers-profile',
            'children' => [
                ['label' => 'Trainer Profiles', 'url' => '/trainers-profile'],
                ['label' => 'Testimonials', 'url' => '/testimonials'],
                ['label' => 'Courses', 'url' => '/courses'],
                ['label' => 'Success Story', 'url' => '/success-story-of-angie'],
            ],
        ],
        [
            'label' => 'Consulting', 'url' => '/consulting-main',
            'children' => [
                ['label' => 'Public/Government Sector', 'url' => '/consulting-gov'],
                ['label' => 'Private Sector', 'url' => '/consulting-private'],
            ],
        ],
        ['label' => 'FAQ’s', 'url' => '/faqs'],
        ['label' => 'Join Us', 'url' => '/hiring-page'],
        ['label' => 'Contact us', 'url' => '/contact-us', 'featured' => true],
    ],
    'clients' => [
        ['image' => 'assets/client-bank-dubai.jpg', 'alt' => 'Commercial Bank of Dubai'],
        ['image' => 'assets/client-saudi-fransi.webp', 'alt' => 'Banque Saudi Fransi'],
        ['image' => 'assets/client-orbis.webp', 'alt' => 'Orbis Business School'],
        ['image' => 'assets/client-dialectica.webp', 'alt' => 'Dialectica'],
        ['image' => 'assets/client-cpa.webp', 'alt' => 'CPA Australia'],
        ['image' => 'assets/client-eif.webp', 'alt' => 'Emirates Institute of Finance'],
        ['image' => 'assets/client-fonco.webp', 'alt' => 'FONCO'],
        ['image' => 'assets/client-gov.webp', 'alt' => 'Government partner'],
        ['image' => 'assets/client-bank.webp', 'alt' => 'Banking partner'],
        ['image' => 'assets/client-partner.webp', 'alt' => 'International partner'],
    ],
    'footer' => [
        'australia' => [
            'AI Anchor Solutions Pty Ltd',
            'ACN No: 691339714',
            'ABN No: 99691339714',
            'Address: U803 5 Waterways Street Wentworth Point NSW 2127 Australia',
        ],
        'malaysia' => [
            'AINCHORS Sdn Bhd',
            '(Formerly registered as Anchors Solution Sdn Bhd)',
            '202001021528 (1377848K)',
            'Tel: +60167022788',
            'Address: Level 13A, Wisma Mont Kiara, 1, Jalan Kiara, Mont Kiara, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur',
        ],
    ],
    'legacy_paths' => [
        'about-us-814253', 'ai-prompt-engineering101-course', 'ai-prompt-engineering101-course-',
        'blogs', 'boooking-page', 'cart', 'cbdccheckoutpage', 'check-out-page-page',
        'check-out-page2', 'check-out-pagecourse-00', 'check-out-pagecourse-individual',
        'check-out-pagecourse-individualaiprompt', 'check-out-pagecourse-individualdataanalytics',
        'check-out-pagecourse-individualdigital', 'check-out-pagecourse23', 'check-out-pagecoursedeale',
        'check-out-pagecoursefintech', 'check-out-pageepayment', 'check-out-pagefinancial', 'checkout',
        'checkout-page-684967', 'checkout-page-physicalcourse-6610', 'checkout-page-onlinecourse',
        'checkout-page123', 'checkout-page6767', 'checkoutfintech', 'consulting-gov', 'consulting-main',
        'consulting-private', 'contact-us', 'courses', 'digitalmarketing', 'events', 'faqs',
        'financialliteracymastery', 'fondy-foong', 'hiring-page', 'individual-aiprompt',
        'individualcbdc', 'individualepayment', 'individualfintech', 'join-our-campaign',
        'package-page', 'package-page-12', 'package-page-4066', 'package-page-4157',
        'package-page-6219', 'package-page-6341', 'package-page-9865', 'package-page-dataanalytics',
        'package-page-page-303665', 'package-pagefi', 'physical-course', 'privacy--policy',
        'products-list', 'single-course', 'single-even', 'success-story-of-angie',
        'terms--conditions', 'testimonials', 'thank-you-7357', 'trainers-profile', 'training',
    ],
];
