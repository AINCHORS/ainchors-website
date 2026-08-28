<?php

namespace App\Http\Controllers\Commerce;

use App\Exceptions\AlreadyOwnedException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\CoursePurchaseEligibilityService;
use App\Services\Commerce\HostedPaymentService;
use App\Services\Courses\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly HostedPaymentService $hostedPayments,
        private readonly CourseAccessService $access,
        private readonly CoursePurchaseEligibilityService $eligibility,
    ) {}

    public function show(Request $request, Product $product): View|RedirectResponse
    {
        $this->assertPurchasable($request, $product);

        if ($product->isCourse() && $this->access->canAccess($request->user(), $product)) {
            return redirect()->route('learn.show', $product);
        }

        if ($product->isPackage() && $this->checkout->isFullyOwned($request->user(), $product)) {
            return redirect()->route('my-courses');
        }

        $key = 'checkout_tokens.'.$product->id;
        $token = $request->session()->get($key) ?? (string) Str::uuid();
        $request->session()->put($key, $token);

        $paymentDriver = (string) config('commerce.payment.driver', 'demo');
        $availableProviders = $paymentDriver === 'hosted' && $product->isCourse()
            ? array_values(array_intersect($this->hostedPayments->availableProviders(), ['stripe']))
            : [];

        return view('checkout.show', compact('product', 'token', 'paymentDriver', 'availableProviders'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->assertPurchasable($request, $product);

        $paymentDriver = (string) config('commerce.payment.driver', 'demo');
        if ($paymentDriver === 'hosted') {
            return $this->startHostedCheckout($request, $product);
        }

        $validator = Validator::make([
            'checkout_token' => $request->input('checkout_token'),
            'card_number' => preg_replace('/\s+/', '', (string) $request->input('card_number')),
            'expiry' => $request->input('expiry'),
            'cvv' => $request->input('cvv'),
        ], [
            'checkout_token' => ['required', 'string', 'max:64'],
            'card_number' => ['required', 'in:4242424242424242'],
            'expiry' => ['required', 'in:12/30'],
            'cvv' => ['required', 'in:123'],
        ], [
            'card_number.in' => 'This is a demo checkout. Please use card 4242 4242 4242 4242.',
            'expiry.in' => 'This is a demo checkout. Please use expiry 12/30.',
            'cvv.in' => 'This is a demo checkout. Please use CVV 123.',
        ]);

        if ($validator->fails()) {
            // Deliberately do not call withInput(): demo card fields must never
            // be flashed into a file/database-backed Laravel session.
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();

        $sessionToken = (string) $request->session()->get('checkout_tokens.'.$product->id);
        abort_unless($sessionToken !== '' && hash_equals($sessionToken, $validated['checkout_token']), 419);

        try {
            $order = $this->checkout->purchase($request->user(), $product, $sessionToken);
        } catch (AlreadyOwnedException) {
            return $product->isCourse()
                ? redirect()->route('learn.show', $product)
                : redirect()->route('my-courses');
        }

        return redirect()->route('checkout.success', $order);
    }

    private function startHostedCheckout(Request $request, Product $product): RedirectResponse
    {
        if ($product->isPackage()) {
            return back()->withErrors(['payment' => 'Course package hosted payment is not enabled yet.']);
        }

        $availableProviders = array_values(array_intersect($this->hostedPayments->availableProviders(), ['stripe']));
        $validator = Validator::make($request->only(['checkout_token', 'payment_provider']), [
            'checkout_token' => ['required', 'string', 'max:64'],
            'payment_provider' => ['required', Rule::in($availableProviders)],
        ], [
            'payment_provider.required' => 'Choose Stripe to continue.',
            'payment_provider.in' => 'The selected payment provider is not available.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();
        $sessionToken = (string) $request->session()->get('checkout_tokens.'.$product->id);
        abort_unless($sessionToken !== '' && hash_equals($sessionToken, $validated['checkout_token']), 419);

        try {
            $hosted = $this->hostedPayments->start(
                $request->user(),
                $product,
                $sessionToken,
                $validated['payment_provider'],
            );
        } catch (AlreadyOwnedException) {
            return $product->isCourse()
                ? redirect()->route('learn.show', $product)
                : redirect()->route('my-courses');
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($hosted['redirect_url']);
    }

    private function assertPurchasable(Request $request, Product $product): void
    {
        abort_unless($this->eligibility->customerCanPurchase($request->user()), 403);

        if ($product->isCourse()) {
            abort_unless($this->eligibility->courseCanBePurchased($product), 404);

            return;
        }

        abort_unless(
            $product->status === 'active'
            && $product->isPackage()
            && $product->billing_type === 'one_time'
            && (float) $product->price > 0
            && array_key_exists($product->currency, config('commerce.supported_currencies', [])),
            404,
        );
    }
}
