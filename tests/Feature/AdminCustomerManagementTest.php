<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_orders_share_a_profile_when_the_phone_number_matches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstProduct = Product::factory()->create(['name' => 'Koka Latte']);
        $firstCheckoutKey = (string) Str::uuid();
        $firstCsrfToken = 'customer-checkout-one';

        $this->withSession([
            '_token' => $firstCsrfToken,
            'cart' => [$firstProduct->id => 1],
            'checkout_key' => $firstCheckoutKey,
        ])->post(route('orders.store'), [
            '_token' => $firstCsrfToken,
            'checkout_key' => $firstCheckoutKey,
            'customer_name' => 'Nadia',
            'customer_phone' => '08123456789',
        ])->assertRedirect();

        $customer = Customer::query()->where('phone', '08123456789')->firstOrFail();
        $firstOrder = Order::query()->firstOrFail();
        $this->assertSame($customer->id, $firstOrder->customer_id);

        $secondProduct = Product::factory()->create(['name' => 'Koka Mocha']);
        $this->actingAs($admin)
            ->post(route('staff.orders.manual.store'), [
                'customer_name' => 'Nadia Walk-in',
                'customer_phone' => '08123456789',
                'items' => [$secondProduct->id => 1],
            ])
            ->assertRedirect(route('staff.orders.index'));

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('Nadia Walk-in', $customer->fresh()->name);
        $this->assertSame(2, $customer->orders()->count());
        $this->assertSame($customer->id, Order::query()->latest('id')->firstOrFail()->customer_id);
    }

    public function test_admin_can_search_customers_and_view_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Sari Pelanggan',
            'phone' => '081111222233',
            'email' => 'sari@example.test',
        ]);
        $product = Product::factory()->create(['name' => 'Koka Cappuccino']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'status' => 'completed',
            'paid_at' => now(),
            'total' => 42000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 42000,
            'quantity' => 1,
            'line_total' => 42000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['q' => '081111']))
            ->assertOk()
            ->assertSee('Sari Pelanggan')
            ->assertSee('Rp 42.000');

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Koka Cappuccino');

        $this->put(route('admin.customers.update', $customer), [
            'name' => 'Sari Baru',
            'phone' => '081111222233',
            'email' => 'sari.baru@example.test',
        ])->assertRedirect(route('admin.customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Sari Baru',
            'email' => 'sari.baru@example.test',
        ]);
    }

    public function test_non_admin_cannot_manage_customer_profiles(): void
    {
        $nonAdmin = User::factory()->create(['role' => 'barista']);

        $this->actingAs($nonAdmin)
            ->get(route('admin.customers.index'))
            ->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
    }
}
