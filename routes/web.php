<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Commerce\CheckoutController;
use App\Http\Controllers\Commerce\PaymentSuccessController;
use App\Http\Controllers\Courses\CourseCatalogController;
use App\Http\Controllers\Courses\CourseLearningController;
use App\Http\Controllers\Courses\CourseMediaController;
use App\Http\Controllers\Courses\MyCoursesController;
use App\Http\Controllers\Legacy\LegacyPageController;
use App\Http\Controllers\Modules\HomeController;
use App\Http\Controllers\Public\ContactSubmissionController;
use App\Http\Controllers\Public\PublicPageController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\PurchaseHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::redirect('/home', '/', 301)->name('home.legacy');

Route::controller(PublicPageController::class)->group(function (): void {
    Route::get('/about-us', 'about')->name('about');
    Route::get('/trainers-profile', 'trainers')->name('trainers');
    Route::get('/testimonials', 'testimonials')->name('testimonials');
    Route::get('/success-story-of-angie', 'successStory')->name('success-story');
    Route::get('/consulting-main', 'consultingMain')->name('consulting.main');
    Route::get('/consulting-gov', 'consultingGovernment')->name('consulting.government');
    Route::get('/consulting-private', 'consultingPrivate')->name('consulting.private');
    Route::get('/faqs', 'faqs')->name('faqs');
    Route::get('/hiring-page', 'hiring')->name('hiring');
    Route::get('/contact-us', 'contact')->name('contact');
    Route::get('/terms--conditions', 'terms')->name('terms');
    Route::get('/privacy--policy', 'privacy')->name('privacy');
    Route::get('/events', 'events')->name('events');
});

Route::get('/_legacy/{path}', [LegacyPageController::class, 'embedded'])
    ->where('path', '[A-Za-z0-9\/-]+')
    ->name('legacy.embedded');
Route::post('/contact-submissions', ContactSubmissionController::class)->name('contact.submit');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:registration')->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/courses', [CourseCatalogController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseCatalogController::class, 'show'])->name('courses.show');
Route::get('/course-packages/{package:slug}', [CourseCatalogController::class, 'package'])->name('packages.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/purchase-history', PurchaseHistoryController::class)->name('purchase-history');
    Route::get('/checkouts/{product:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkouts/{product:slug}', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders/{order:order_number}/success', PaymentSuccessController::class)->name('checkout.success');
    Route::get('/my-courses', MyCoursesController::class)->name('my-courses');
    Route::get('/learn/{course:slug}', CourseLearningController::class)->name('learn.show');
    Route::get('/course-media/{course:slug}/video', [CourseMediaController::class, 'video'])->name('course-media.video');
    Route::get('/course-media/{course:slug}/slides', [CourseMediaController::class, 'slides'])->name('course-media.slides');
});

$legacyCourseRedirects = [
    '/individual-aiprompt' => '/courses/ai-prompt-engineering-101',
    '/ai-prompt-engineering101-course' => '/courses/ai-prompt-engineering-101',
    '/digitalmarketing' => '/courses/digital-marketing-using-ai',
    '/package-page-dataanalytics' => '/courses/data-analytics',
    '/package-page-page-303665' => '/courses/sql-for-data-analytics',
    '/financialliteracymastery' => '/courses/financial-literacy-mastery',
    '/individualepayment' => '/courses/e-payment-fundamentals',
    '/individualfintech' => '/courses/fintech-fundamentals',
    '/individualcbdc' => '/courses/central-bank-digital-currency-cbdc',
];

foreach ($legacyCourseRedirects as $from => $to) {
    Route::redirect($from, $to, 301);
}

$legacyCheckoutRedirects = [
    '/check-out-pagecourse-individualaiprompt' => '/checkouts/ai-prompt-engineering-101',
    '/check-out-pagecourse-individualdigital' => '/checkouts/digital-marketing-using-ai',
    '/check-out-pagecourse-individualdataanalytics' => '/checkouts/data-analytics',
    '/check-out-pagecourse-individual' => '/checkouts/sql-for-data-analytics',
    '/check-out-pagefinancial' => '/checkouts/financial-literacy-mastery',
    '/check-out-pageepayment' => '/checkouts/e-payment-fundamentals',
    '/checkoutfintech' => '/checkouts/fintech-fundamentals',
    '/cbdccheckoutpage' => '/checkouts/central-bank-digital-currency-cbdc',
    '/check-out-page-page' => '/checkouts/learning-course-package-deal',
    '/check-out-pagecourse-00' => '/checkouts/learning-course-package-deal',
    '/check-out-pagecourse23' => '/checkouts/learning-course-package-deal',
    '/check-out-pagecoursedeale' => '/checkouts/learning-course-package-deal',
    '/check-out-page2' => '/checkouts/learning-course-package-deal',
];

foreach ($legacyCheckoutRedirects as $from => $to) {
    Route::redirect($from, $to, 302);
}

Route::get('/{path}', LegacyPageController::class)
    ->where('path', '[A-Za-z0-9\/-]+')
    ->name('legacy.page');
