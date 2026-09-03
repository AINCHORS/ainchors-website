<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\ExternalInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\ExternalInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UserPurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_history_is_user_scoped_uses_snapshots_and_only_shows_final_states(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $paidCourse = $this->course('paid-course', 'Current Paid Course Name');
        $awaitingCourse = $this->course('awaiting-course', 'Current Awaiting Course Name');
        $failedCourse = $this->course('failed-course', 'Current Failed Course Name');
        $otherCourse = $this->course('other-course', 'Other User Current Course');

        $paidOrder = $this->order($user, $paidCourse, 'AIN-HISTORY-PAID', 'completed', 'paid', 'Purchased Course Snapshot');
        $awaitingOrder = $this->order($user, $awaitingCourse, 'AIN-HISTORY-AWAITING', 'awaiting_payment', 'pending', 'Awaiting Course Snapshot');
        $failedOrder = $this->order($user, $failedCourse, 'AIN-HISTORY-FAILED', 'awaiting_payment', 'failed', 'Failed Course Snapshot');
        $otherOrder = $this->order($otherUser, $otherCourse, 'AIN-HISTORY-OTHER', 'completed', 'paid', 'Other User Secret Snapshot');
        $paidOrder->payments()->firstOrFail()->update(['provider_data' => ['private_marker' => 'DO-NOT-EXPOSE-PROVIDER-DATA']]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'product_id' => $paidCourse->id,
            'source_order_item_id' => $paidOrder->items()->value('id'),
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        Enrollment::query()->create([
            'user_id' => $otherUser->id,
            'product_id' => $otherCourse->id,
            'source_order_item_id' => $otherOrder->items()->value('id'),
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $paidCourse->update(['name' => 'Renamed Product Must Not Replace Snapshot']);

        $response = $this->actingAs($user)->get(route('purchase-history'));

        $response->assertOk()
            ->assertSee('data-purchase-history-desktop', false)
            ->assertSee('data-purchase-history-cards', false)
            ->assertSee('data-course-carousel', false)
            ->assertSee('course-carousel-viewport', false)
            ->assertSee('data-carousel-pagination', false)
            ->assertSee('data-carousel-status', false)
            ->assertSee('data-purchase-order-reference', false)
            ->assertSee('line-clamp-2', false)
            ->assertSee('data-purchase-no-action', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee('data-purchase-product', false)
            ->assertSee('min-h-[2.75rem]', false)
            ->assertSee('min-h-[4.5rem]', false)
            ->assertSee($paidOrder->order_number)
            ->assertSee($failedOrder->order_number)
            ->assertSee('Purchased Course Snapshot')
            ->assertSee('Failed Course Snapshot')
            ->assertDontSee($awaitingOrder->order_number)
            ->assertDontSee('Awaiting Course Snapshot')
            ->assertDontSee('Awaiting payment')
            ->assertSee('Failed')
            ->assertSee('Stripe')
            ->assertDontSee(route('learn.show', $paidCourse), false)
            ->assertDontSee('Renamed Product Must Not Replace Snapshot')
            ->assertDontSee($otherOrder->order_number)
            ->assertDontSee('Other User Secret Snapshot')
            ->assertDontSee('DO-NOT-EXPOSE-PROVIDER-DATA');

        $this->assertSame(0, substr_count($response->getContent(), 'Access Course'));
    }

    public function test_failed_payment_grants_no_course_access(): void
    {
        $user = User::factory()->create();
        $course = $this->course('failed-access-course', 'Failed Access Course');
        $this->order($user, $course, 'AIN-FAILED-NO-ACCESS', 'awaiting_payment', 'failed', 'Failed Access Snapshot');

        $this->actingAs($user)->get(route('purchase-history'))
            ->assertOk()
            ->assertSee('Failed Access Snapshot')
            ->assertSee('Failed')
            ->assertDontSee('Access Course');
        $this->actingAs($user)->get(route('my-courses'))->assertOk()->assertDontSee($course->name);
        $this->actingAs($user)->get(route('learn.show', $course))->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_provider_neutral_invoice_link_is_server_validated_and_user_scoped(): void
    {
        config(['commerce.invoices.provider_hosts.stripe' => ['billing.example.test']]);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = $this->course('invoice-course', 'Invoice Course');
        $order = $this->order($owner, $course, 'AIN-INVOICE-READY', 'completed', 'paid', 'Invoice Course Snapshot');
        $invoice = app(ExternalInvoiceService::class)->record(
            $order,
            'stripe',
            'stripe-invoice-fixture-1',
            'https://billing.example.test/customer/invoice-fixture-1',
            'INV-1001',
        );

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'stripe']);
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'external_reference' => 'stripe-invoice-fixture-1',
            'invoice_number' => 'INV-1001',
            'status' => 'issued',
        ]);

        $this->actingAs($owner)->get(route('purchase-history'))
            ->assertOk()
            ->assertSee('Invoice')
            ->assertSee('data-purchase-receipt', false)
            ->assertSee('data-purchase-receipt-desktop', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee('hover:bg-ainchors-green-hero', false)
            ->assertSee('hover:text-ainchors-navy', false)
            ->assertSee(route('purchase-history.invoice', $invoice), false)
            ->assertDontSee('https://billing.example.test/customer/invoice-fixture-1');
        $this->actingAs($owner)->get(route('purchase-history.invoice', $invoice))
            ->assertRedirect('https://billing.example.test/customer/invoice-fixture-1');
        $this->actingAs($otherUser)->get(route('purchase-history.invoice', $invoice))->assertNotFound();

        $this->expectException(InvalidArgumentException::class);
        app(ExternalInvoiceService::class)->record(
            $order,
            'stripe',
            'untrusted-fixture',
            'https://attacker.example.test/not-an-invoice',
        );
    }

    public function test_tampered_or_void_external_invoice_is_not_exposed(): void
    {
        config(['commerce.invoices.provider_hosts.stripe' => ['billing.example.test']]);

        $user = User::factory()->create();
        $course = $this->course('tampered-invoice-course', 'Tampered Invoice Course');
        $order = $this->order($user, $course, 'AIN-INVOICE-TAMPERED', 'completed', 'paid', 'Tampered Invoice Snapshot');
        $invoice = ExternalInvoice::query()->create([
            'order_id' => $order->id,
            'provider' => 'future_provider',
            'external_reference' => 'tampered-invoice-fixture',
            'invoice_number' => 'INV-TAMPERED',
            'invoice_url' => 'https://attacker.example.test/arbitrary',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        $this->actingAs($user)->get(route('purchase-history'))
            ->assertOk()
            ->assertDontSee(route('purchase-history.invoice', $invoice), false)
            ->assertDontSee('attacker.example.test');
        $this->actingAs($user)->get(route('purchase-history.invoice', $invoice))->assertNotFound();

        $invoice->update(['invoice_url' => 'https://billing.example.test/void', 'status' => 'void']);
        $this->actingAs($user)->get(route('purchase-history.invoice', $invoice))->assertNotFound();
    }

    private function course(string $slug, string $name): Product
    {
        return Product::query()->create([
            'type' => 'course',
            'sku' => 'PH-'.strtoupper(str_replace('-', '_', $slug)),
            'name' => $name,
            'slug' => $slug,
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
        ]);
    }

    private function order(
        User $user,
        Product $product,
        string $orderNumber,
        string $orderStatus,
        string $paymentStatus,
        string $snapshotName,
    ): Order {
        $order = Order::query()->create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'status' => $orderStatus,
            'currency' => 'USD',
            'subtotal' => 19,
            'discount_total' => 0,
            'tax_total' => 0,
            'total_amount' => 19,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $snapshotName,
            'quantity' => 1,
            'unit_price' => 19,
            'line_total' => 19,
            'metadata' => ['product_type' => 'course', 'currency' => 'USD', 'course_product_ids' => [$product->id]],
        ]);
        $order->payments()->create([
            'provider' => 'stripe',
            'provider_transaction_id' => 'cs_test_'.strtolower(str_replace('-', '_', $orderNumber)),
            'amount' => 19,
            'currency' => 'USD',
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ]);

        return $order;
    }
}
