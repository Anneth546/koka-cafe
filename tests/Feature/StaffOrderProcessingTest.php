<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffOrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_processed_from_cashier_payment_to_completion(): void
    {
        $product = Product::factory()->create([
            'name' => 'Koka Latte',
            'price' => 25000,
        ]);
        $checkoutKey = (string) Str::uuid();
        $csrfToken = 'order-lifecycle-token';

        $this->withSession([
            '_token' => $csrfToken,
            'cart' => [$product->id => 1],
            'checkout_key' => $checkoutKey,
        ])->post('/pesanan', [
            '_token' => $csrfToken,
            'checkout_key' => $checkoutKey,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/staff/pesanan')
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Konfirmasi pembayaran');

        $this->actingAs($admin)
            ->patch('/staff/pesanan/'.$order->id.'/status', [
                'status' => 'paid',
                'payment_method' => 'cash',
                'amount_received' => 30000,
            ])
            ->assertRedirect('/staff/pesanan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
        $this->assertSame('30000.00', $order->fresh()->amount_received);
        $this->assertSame('5000.00', $order->fresh()->change_due);
        $this->assertNotNull($order->fresh()->paid_at);

        $this->actingAs($admin)
            ->get('/staff/pesanan')
            ->assertOk()
            ->assertSee('Mulai menyiapkan');

        $this->actingAs($admin)
            ->patch('/staff/pesanan/'.$order->id.'/status', ['status' => 'preparing'])
            ->assertRedirect('/staff/pesanan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);

        $this->actingAs($admin)
            ->patch('/staff/pesanan/'.$order->id.'/status', ['status' => 'ready'])
            ->assertRedirect('/staff/pesanan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'ready',
        ]);

        $this->actingAs($admin)
            ->patch('/staff/pesanan/'.$order->id.'/status', ['status' => 'completed'])
            ->assertRedirect('/staff/pesanan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);

        $this->get('/pesanan/'.$order->public_token)
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Koka Latte');
    }

    public function test_non_admin_staff_cannot_access_or_process_orders(): void
    {
        $paidOrder = Order::factory()->create([
            'order_number' => 'KOKA-PAID-01',
            'status' => 'paid',
        ]);
        $barista = User::factory()->create(['role' => 'barista']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($barista)
            ->get('/staff/pesanan')
            ->assertForbidden();

        $this->actingAs($cashier)
            ->patch('/staff/pesanan/'.$paidOrder->id.'/status', ['status' => 'preparing'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $paidOrder->id, 'status' => 'paid']);
    }

    public function test_customer_cannot_access_or_process_staff_orders(): void
    {
        $order = Order::factory()->create();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get('/staff/pesanan')->assertRedirect('/staff/login');

        $this->actingAs($customer)
            ->get('/staff/pesanan')
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch('/staff/pesanan/'.$order->id.'/status', ['status' => 'paid'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending_payment',
        ]);
    }

    public function test_owner_cannot_open_order_management(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->get('/staff/pesanan')
            ->assertForbidden();
    }

    public function test_admin_can_process_an_order_through_every_stage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending_payment']);

        foreach (['paid', 'preparing', 'ready', 'completed'] as $status) {
            $payload = ['status' => $status];

            if ($status === 'paid') {
                $payload['payment_method'] = 'qris';
            }

            $this->actingAs($admin)
                ->patch('/staff/pesanan/'.$order->id.'/status', $payload)
                ->assertRedirect('/staff/pesanan');

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $status,
            ]);
        }
    }

    public function test_cash_payment_rejects_an_amount_below_the_order_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'total' => 25000,
        ]);

        $this->actingAs($admin)
            ->from('/staff/pesanan')
            ->patch('/staff/pesanan/'.$order->id.'/status', [
                'status' => 'paid',
                'payment_method' => 'cash',
                'amount_received' => 20000,
            ])
            ->assertRedirect('/staff/pesanan')
            ->assertSessionHasErrors('amount_received');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending_payment',
            'payment_method' => null,
        ]);
    }

    public function test_admin_can_open_a_queue_of_paid_and_in_progress_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $unpaidOrder = Order::factory()->create([
            'order_number' => 'KOKA-QUEUE-UNPAID',
            'status' => 'pending_payment',
        ]);
        $paidOrder = Order::factory()->create([
            'order_number' => 'KOKA-QUEUE-PAID',
            'status' => 'paid',
        ]);
        $preparingOrder = Order::factory()->create([
            'order_number' => 'KOKA-QUEUE-PREPARING',
            'status' => 'preparing',
        ]);

        $this->actingAs($admin)
            ->get('/staff/pesanan?queue=1')
            ->assertOk()
            ->assertSee('Antrean dapur')
            ->assertSee($paidOrder->order_number)
            ->assertSee($preparingOrder->order_number)
            ->assertDontSee($unpaidOrder->order_number);
    }
}
