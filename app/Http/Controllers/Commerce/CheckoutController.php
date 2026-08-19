<?php

namespace App\Http\Controllers\Commerce;

use App\Exceptions\AlreadyOwnedException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Commerce\CheckoutService;
use App\Services\Courses\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CourseAccessService $access,
    ) {}

    public function show(Request $request, Product $product): View|RedirectResponse
    {
        $this->assertPurchasable($product);

        if ($product->isCourse() && $this->access->canAccess($request->user(), $product)) {
            return redirect()->route('learn.show', $product);
        }

        if ($product->isPackage() && $this->checkout->isFullyOwned($request->user(), $product)) {
            return redirect()->route('my-courses');
        }

        $key = 'checkout_tokens.'.$product->id;
        $token = $request->session()->get($key) ?? (string) Str::uuid();
        $request->session()->put($key, $token);

        return view('checkout.show', compact('product', 'token'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->assertPurchasable($product);

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

    private function assertPurchasable(Product $product): void
    {
        abort_unless($product->status === 'active' && in_array($product->type, ['course', 'course_package'], true), 404);
    }
}
