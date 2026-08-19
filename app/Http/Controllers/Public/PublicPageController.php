<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    /** @var array<string, array{slug: string, title: string}> */
    private const PAGES = [
        'about' => ['slug' => 'about-us-814253', 'title' => 'About Us | AINCHORS'],
        'trainers' => ['slug' => 'trainers-profile', 'title' => 'Trainer Profiles | AINCHORS'],
        'testimonials' => ['slug' => 'testimonials', 'title' => 'Testimonials | AINCHORS'],
        'successStory' => ['slug' => 'success-story-of-angie', 'title' => 'Success Story | AINCHORS'],
        'consultingMain' => ['slug' => 'consulting-main', 'title' => 'Consulting | AINCHORS'],
        'consultingGovernment' => ['slug' => 'consulting-gov', 'title' => 'Public / Government Consulting | AINCHORS'],
        'consultingPrivate' => ['slug' => 'consulting-private', 'title' => 'Private Sector Consulting | AINCHORS'],
        'faqs' => ['slug' => 'faqs', 'title' => 'FAQs | AINCHORS'],
        'hiring' => ['slug' => 'hiring-page', 'title' => 'Join Us | AINCHORS'],
        'contact' => ['slug' => 'contact-us', 'title' => 'Contact Us | AINCHORS'],
        'terms' => ['slug' => 'terms--conditions', 'title' => 'Terms & Conditions | AINCHORS'],
        'privacy' => ['slug' => 'privacy--policy', 'title' => 'Privacy Policy | AINCHORS'],
        'events' => ['slug' => 'events', 'title' => 'Events | AINCHORS'],
    ];

    public function about(): View { return $this->legacy(self::PAGES['about']); }
    public function trainers(): View { return $this->legacy(self::PAGES['trainers']); }
    public function testimonials(): View { return $this->legacy(self::PAGES['testimonials']); }
    public function successStory(): View { return $this->legacy(self::PAGES['successStory']); }
    public function consultingMain(): View { return $this->legacy(self::PAGES['consultingMain']); }
    public function consultingGovernment(): View { return $this->legacy(self::PAGES['consultingGovernment']); }
    public function consultingPrivate(): View { return $this->legacy(self::PAGES['consultingPrivate']); }
    public function faqs(): View { return $this->legacy(self::PAGES['faqs']); }
    public function hiring(): View { return $this->legacy(self::PAGES['hiring']); }
    public function contact(): View { return $this->legacy(self::PAGES['contact']); }
    public function terms(): View { return $this->legacy(self::PAGES['terms']); }
    public function privacy(): View { return $this->legacy(self::PAGES['privacy']); }
    public function events(): View { return $this->legacy(self::PAGES['events']); }

    /** @param array{slug: string, title: string} $page */
    private function legacy(array $page): View
    {
        return view('public.legacy-page', [
            'title' => $page['title'],
            'legacySource' => route('legacy.embedded', ['path' => $page['slug']]),
        ]);
    }
}
