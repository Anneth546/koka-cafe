<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_walk_in_order_with_price_snapshots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Koka Latte',
            'price' => 27000,
            'is_available' => true,
        ]);
        $table = DiningTable::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post('/staff/pesanan/manual', [
                'customer_name' => 'Pelanggan Walk-in',
                'customer_phone' => '08123456789',
                'dining_table_id' => $table->id,
                'items' => [$product->id => 2],
            ])
            ->assertRedirect('/staff/pesanan');

        $order = Order::query()->where('customer_name', 'Pelanggan Walk-in')->firstOrFail();
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('54000.00', $order->total);
        $this->assertSame($table->id, $order->dining_table_id);
        $this->assertSame('08123456789', $order->customer_phone);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Koka Latte',
            'quantity' => 2,
            'line_total' => 54000,
        ]);
    }

    public function test_walk_in_order_rejects_products_that_are_not_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['is_available' => false]);

        $this->actingAs($admin)
            ->from('/staff/pesanan')
            ->post('/staff/pesanan/manual', [
                'items' => [$product->id => 1],
            ])
            ->assertRedirect('/staff/pesanan')
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_can_filter_orders_by_number_table_and_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table = DiningTable::factory()->create(['number' => '91', 'label' => 'Meja Filter']);
        $selectedOrder = Order::factory()->create([
            'order_number' => 'KOKA-FILTER-ME',
            'dining_table_id' => $table->id,
            'created_at' => now(),
        ]);
        $otherOrder = Order::factory()->create([
            'order_number' => 'KOKA-FILTER-OTHER',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get('/staff/pesanan?order_number=KOKA-FILTER-ME&table_id='.$table->id.'&from='.today()->toDateString().'&to='.today()->toDateString())
            ->assertOk()
            ->assertSee($selectedOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    public function test_admin_can_cancel_an_unpaid_order_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending_payment']);

        $this->actingAs($admin)
            ->patch('/staff/pesanan/'.$order->id.'/cancel', [
                'cancel_reason' => 'Pelanggan mengubah pesanan.',
            ])
            ->assertRedirect('/staff/pesanan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancel_reason' => 'Pelanggan mengubah pesanan.',
        ]);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public function test_admin_cannot_cancel_a_paid_order_without_a_refund_flow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/staff/pesanan')
            ->patch('/staff/pesanan/'.$order->id.'/cancel', [
                'cancel_reason' => 'Salah input.',
            ])
            ->assertRedirect('/staff/pesanan')
            ->assertSessionHasErrors('cancel_reason');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'cancelled_at' => null,
        ]);
    }

    public function test_non_admin_cannot_create_or_cancel_orders(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $order = Order::factory()->create(['status' => 'pending_payment']);

        $this->actingAs($owner)
            ->post('/staff/pesanan/manual', ['items' => []])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch('/staff/pesanan/'.$order->id.'/cancel', [
                'cancel_reason' => 'Tidak sesuai.',
            ])
            ->assertForbidden();
    }
}
