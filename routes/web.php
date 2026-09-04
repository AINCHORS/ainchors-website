<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\ConsultationController as AdminConsultationController;
use App\Http\Controllers\Admin\CourseContentController as AdminCourseContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\ExternalInvoiceRedirectController as AdminExternalInvoiceRedirectController;
use App\Http\Controllers\Admin\JobApplicationController as AdminJobApplicationController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PackageMembershipController as AdminPackageMembershipController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Commerce\CheckoutController;
use App\Http\Controllers\Commerce\ExternalInvoiceRedirectController;
use App\Http\Controllers\Commerce\HostedPaymentController;
use App\Http\Controllers\Commerce\PaymentSuccessController;
use App\Http\Controllers\Commerce\PaymentWebhookController;
use App\Http\Controllers\Courses\CourseCatalogController;
use App\Http\Controllers\Courses\CourseLearningController;
use App\Http\Controllers\Courses\CourseMediaController;
use App\Http\Controllers\Courses\MyCoursesController;
use App\Http\Controllers\Legacy\LegacyPageController;
use App\Http\Controllers\Modules\Company\AboutController;
use App\Http\Controllers\Modules\Company\AngieFoongController;
use App\Http\Controllers\Modules\Company\CareersController;
use App\Http\Controllers\Modules\Company\JobApplicationController;
use App\Http\Controllers\Modules\Consulting\GovernmentBookingController;
use App\Http\Controllers\Modules\HomeController;
use App\Http\Controllers\Modules\Support\ContactController;
use App\Http\Controllers\Modules\Support\FaqController;
use App\Http\Controllers\Modules\Support\PrivacyController;
use App\Http\Controllers\Modules\Support\TermsController;
use App\Http\Controllers\Public\ContactSubmissionController;
use App\Http\Controllers\Public\PublicPageController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\PurchaseHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::redirect('/home', '/', 301)->name('home.legacy');
Route::get('/about-us', AboutController::class)->name('about');
Route::get('/angie-foong', AngieFoongController::class)->name('angie-foong');
Route::get('/faqs', FaqController::class)->name('faqs');
Route::get('/join-us', CareersController::class)->name('hiring');
Route::get('/contact-us', ContactController::class)->name('contact');
Route::get('/terms--conditions', TermsController::class)->name('terms');
Route::get('/privacy--policy', PrivacyController::class)->name('privacy');

Route::controller(PublicPageController::class)->group(function (): void {
    Route::get('/trainers-profile', 'trainers')->name('trainers');
    Route::get('/fondy-foong', 'fondyFoong')->name('fondy-foong');
    Route::get('/testimonials', 'testimonials')->name('testimonials');
    Route::get('/success-story-of-angie', 'successStory')->name('success-story');
    Route::get('/consulting-main', 'consultingMain')->name('consulting.main');
    Route::get('/consulting-gov', 'consultingGovernment')->name('consulting.government');
    Route::get('/consulting-private', 'consultingPrivate')->name('consulting.private');
});

Route::get('/_legacy/{path}', [LegacyPageController::class, 'embedded'])
    ->where('path', '[A-Za-z0-9\/-]+')
    ->name('legacy.embedded');
Route::post('/contact-submissions', ContactSubmissionController::class)->name('contact.submit');

Route::post('/payments/stripe/webhook', [PaymentWebhookController::class, 'stripe'])->name('payments.stripe.webhook');
Route::post('/payments/paypal/webhook', [PaymentWebhookController::class, 'paypal'])->name('payments.paypal.webhook');

Route::redirect('/boooking-page', '/consulting-main', 301)->name('consulting.booking.legacy');
Route::redirect('/booking-page', '/consulting-main', 301)->name('consulting.booking.legacy-alias');
Route::redirect('/consulting-gov/booking', '/consulting-gov', 301)->name('consulting.booking.government-legacy');
Route::redirect('/consulting-private/booking', '/consulting-private', 301)->name('consulting.booking.private-legacy');
Route::post('/consulting-booking/select', [GovernmentBookingController::class, 'select'])->name('consulting.booking.select');
Route::get('/consulting-booking', [GovernmentBookingController::class, 'create'])->name('consulting.booking');
Route::post('/consulting-booking', [GovernmentBookingController::class, 'store'])->name('consulting.booking.store');

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

Route::middleware(['auth', 'password.changed'])->group(function (): void {
    Route::get('/join-us/apply', [JobApplicationController::class, 'create'])->name('job-applications.create');
    Route::post('/join-us/apply', [JobApplicationController::class, 'store'])->name('job-applications.store');
    Route::get('/join-us/application-success', [JobApplicationController::class, 'success'])->name('job-applications.success');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/purchase-history', PurchaseHistoryController::class)->name('purchase-history');
    Route::get('/purchase-history/invoices/{externalInvoice}', ExternalInvoiceRedirectController::class)->name('purchase-history.invoice');
    Route::get('/checkouts/{product:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::get('/checkouts/{product:slug}/paypal/waiting-target', [CheckoutController::class, 'paypalWaitingTarget'])->name('checkout.paypal.waiting-target');
    Route::post('/checkouts/{product:slug}', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/payments/stripe/{order:order_number}/return', [HostedPaymentController::class, 'stripeReturn'])->name('payments.stripe.return');
    Route::get('/payments/paypal/{order:order_number}/waiting', [HostedPaymentController::class, 'paypalWaiting'])->name('payments.paypal.waiting');
    Route::get('/payments/paypal/{order:order_number}/waiting/status', [HostedPaymentController::class, 'paypalStatus'])->name('payments.paypal.status');
    Route::get('/payments/{provider}/{order:order_number}/cancel', [HostedPaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/orders/{order:order_number}/payment-unsuccessful', [HostedPaymentController::class, 'failed'])->name('checkout.failed');
    Route::get('/orders/{order:order_number}/success', PaymentSuccessController::class)->name('checkout.success');
    Route::get('/my-courses', MyCoursesController::class)->name('my-courses');
    Route::get('/learn/{course:slug}', CourseLearningController::class)->name('learn.show');
    Route::get('/course-media/{course:slug}/video', [CourseMediaController::class, 'video'])->name('course-media.video');
    Route::get('/course-media/{course:slug}/slides', [CourseMediaController::class, 'slides'])->name('course-media.slides');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::match(['put', 'patch'], '/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.password.reset');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch'], '/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/status', [AdminProductController::class, 'updateStatus'])->name('products.status');
    Route::get('/products/{product}/package-courses', [AdminPackageMembershipController::class, 'index'])->name('package-members.index');
    Route::post('/products/{product}/package-courses', [AdminPackageMembershipController::class, 'store'])->name('package-members.store');
    Route::patch('/products/{product}/package-courses/reorder', [AdminPackageMembershipController::class, 'reorder'])->name('package-members.reorder');
    Route::delete('/products/{product}/package-courses/{course}', [AdminPackageMembershipController::class, 'destroy'])->name('package-members.destroy');

    Route::get('/course-content', [AdminCourseContentController::class, 'index'])->name('course-content.index');
    Route::get('/course-content/create', [AdminCourseContentController::class, 'create'])->name('course-content.create');
    Route::post('/course-content', [AdminCourseContentController::class, 'store'])->name('course-content.store');
    Route::get('/course-content/{courseContent}/edit', [AdminCourseContentController::class, 'edit'])->name('course-content.edit');
    Route::get('/course-content/{courseContent}/video-preview', [AdminCourseContentController::class, 'videoPreview'])->name('course-content.video-preview');
    Route::get('/course-content/{courseContent}/slides-preview', [AdminCourseContentController::class, 'slidesPreview'])->name('course-content.slides-preview');
    Route::match(['put', 'patch'], '/course-content/{courseContent}', [AdminCourseContentController::class, 'update'])->name('course-content.update');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::get('/invoices/{externalInvoice}', AdminExternalInvoiceRedirectController::class)->name('invoices.show');

    Route::get('/enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('/enrollments', [AdminEnrollmentController::class, 'store'])->name('enrollments.store');
    Route::patch('/enrollments/{enrollment}/revoke', [AdminEnrollmentController::class, 'revoke'])->name('enrollments.revoke');

    Route::get('/job-applications', [AdminJobApplicationController::class, 'index'])->name('job-applications.index');
    Route::get('/job-applications/{jobApplication}', [AdminJobApplicationController::class, 'show'])->name('job-applications.show');
    Route::match(['put', 'patch'], '/job-applications/{jobApplication}', [AdminJobApplicationController::class, 'update'])->name('job-applications.update');
    Route::get('/job-applications/{jobApplication}/resume', [AdminJobApplicationController::class, 'resume'])->name('job-applications.resume');

    Route::get('/contact-submissions', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::get('/contact-submissions/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
    Route::match(['put', 'patch'], '/contact-submissions/{lead}', [AdminLeadController::class, 'update'])->name('leads.update');

    Route::get('/consultations', [AdminConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/{consultation}', [AdminConsultationController::class, 'show'])->name('consultations.show');
    Route::match(['put', 'patch'], '/consultations/{consultation}', [AdminConsultationController::class, 'update'])->name('consultations.update');

    Route::get('/audit-log', [AdminAuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/audit-log/{auditLog}', [AdminAuditLogController::class, 'show'])->name('audit-log.show');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::match(['put', 'patch'], '/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
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