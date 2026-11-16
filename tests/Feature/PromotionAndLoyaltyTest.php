<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromotionAndLoyaltyTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_and_update_a_promotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.promotions.store'), [
            'code' => 'hemat10',
            'name' => 'Hemat Sepuluh Persen',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order' => 50000,
            'maximum_discount' => 15000,
            'usage_limit' => 20,
            'is_active' => 1,
        ])->assertRedirect(route('admin.promotions.index'));

        $promotion = Promotion::query()->firstOrFail();
        $this->get(route('admin.promotions.index'))
            ->assertOk()
            ->assertSee('HEMAT10')
            ->assertSee('Voucher terdaftar');
        $this->assertSame('HEMAT10', $promotion->code);
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'discount_type' => 'percentage',
            'discount_value' => '10.00',
            'usage_limit' => 20,
            'is_active' => 1,
        ]);

        $this->put(route('admin.promotions.update', $promotion), [
            'code' => 'hemat10',
            'name' => 'Hemat Dua Puluh Persen',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'minimum_order' => 60000,
            'maximum_discount' => 20000,
            'usage_limit' => 20,
            'is_active' => 0,
        ])->assertRedirect(route('admin.promotions.index'));

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'code' => 'HEMAT10',
            'name' => 'Hemat Dua Puluh Persen',
            'discount_value' => '20.00',
            'is_active' => 0,
        ]);
    }

    public function test_checkout_applies_one_voucher_and_redeemed_points_and_earns_points_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Nadia',
            'phone' => '08123456789',
        ]);
        $customer->forceFill(['loyalty_points' => 200])->save();
        $product = Product::factory()->create(['price' => 100000]);
        $promotion = Promotion::query()->create([
            'code' => 'HEMAT10',
            'name' => 'Diskon persen berbatas',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'minimum_order' => 0,
            'maximum_discount' => 10000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);
        $checkoutKey = (string) Str::uuid();

        $this->withSession([
            'cart' => [$product->id => 1],
            'checkout_key' => $checkoutKey,
        ])->post(route('orders.store'), [
            'checkout_key' => $checkoutKey,
            'customer_name' => 'Nadia',
            'customer_phone' => $customer->phone,
            'promotion_code' => 'hemat10',
            'redeem_points' => 1,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'promotion_id' => $promotion->id,
            'promotion_code' => 'HEMAT10',
            'subtotal' => '100000.00',
            'discount_amount' => '30000.00',
            'loyalty_points_redeemed' => 200,
            'total' => '70000.00',
        ]);
        $this->assertSame(0, $customer->fresh()->loyalty_points);
        $this->assertDatabaseHas('promotion_usages', [
            'promotion_id' => $promotion->id,
            'order_id' => $order->id,
            'discount_amount' => '10000.00',
        ]);
        $this->assertSame(1, $promotion->fresh()->usage_count);

        $this->actingAs($admin)
            ->patch(route('staff.orders.update-status', $order), [
                'status' => 'paid',
                'payment_method' => 'qris',
            ])
            ->assertRedirect(route('staff.orders.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'loyalty_points_earned' => 7,
        ]);
        $this->assertSame(7, $customer->fresh()->loyalty_points);

        $this->from(route('staff.orders.index'))
            ->patch(route('staff.orders.update-status', $order), [
                'status' => 'paid',
                'payment_method' => 'qris',
            ])
            ->assertRedirect(route('staff.orders.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame(7, $customer->fresh()->loyalty_points);
        $this->assertSame(1, PromotionUsage::query()->where('order_id', $order->id)->count());

        $this->get(route('orders.show', $order->public_token))
            ->assertOk()
            ->assertSee('Diskon')
            ->assertSee('Rp 30.000')
            ->assertSee('Poin yang diperoleh: 7');
    }

    public function test_cancelled_unpaid_order_refunds_redeemed_points_and_releases_voucher_quota(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Rani',
            'phone' => '081122233344',
        ]);
        $customer->forceFill(['loyalty_points' => 100])->save();
        $product = Product::factory()->create(['price' => 20000]);
        $promotion = Promotion::query()->create([
            'code' => 'POTONG5',
            'name' => 'Potongan lima ribu',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'minimum_order' => 0,
            'usage_limit' => 1,
            'is_active' => true,
        ]);
        $checkoutKey = (string) Str::uuid();

        $this->withSession([
            'cart' => [$product->id => 1],
            'checkout_key' => $checkoutKey,
        ])->post(route('orders.store'), [
            'checkout_key' => $checkoutKey,
            'customer_phone' => $customer->phone,
            'promotion_code' => 'POTONG5',
            'redeem_points' => 1,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame(0, $customer->fresh()->loyalty_points);
        $this->assertSame(1, $promotion->fresh()->usage_count);
        $this->assertSame('5000.00', $order->total);

        $secondCheckoutKey = (string) Str::uuid();
        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $secondCheckoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $secondCheckoutKey,
                'promotion_code' => 'POTONG5',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('orders', 1);

        $this->actingAs($admin)
            ->patch(route('staff.orders.cancel', $order), ['cancel_reason' => 'Pelanggan berubah pikiran'])
            ->assertRedirect(route('staff.orders.index'));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertSame(100, $customer->fresh()->loyalty_points);
        $this->assertSame(0, $promotion->fresh()->usage_count);
        $this->assertDatabaseCount('promotion_usages', 0);

        $this->from(route('staff.orders.index'))
            ->patch(route('staff.orders.cancel', $order), ['cancel_reason' => 'Percobaan ulang'])
            ->assertRedirect(route('staff.orders.index'))
            ->assertSessionHasErrors('cancel_reason');

        $this->assertSame(100, $customer->fresh()->loyalty_points);
        $this->assertSame(0, $promotion->fresh()->usage_count);
    }

    public function test_checkout_rejects_an_unknown_promotion_without_creating_an_order(): void
    {
        $product = Product::factory()->create();
        $checkoutKey = (string) Str::uuid();

        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $checkoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $checkoutKey,
                'promotion_code' => 'NOT-A-VOUCHER',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_checkout_rejects_a_promotion_below_its_minimum_order(): void
    {
        $product = Product::factory()->create(['price' => 20000]);
        Promotion::query()->create([
            'code' => 'MINIMUM50',
            'name' => 'Minimum lima puluh ribu',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'minimum_order' => 50000,
            'is_active' => true,
        ]);
        $checkoutKey = (string) Str::uuid();

        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $checkoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $checkoutKey,
                'promotion_code' => 'MINIMUM50',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_checkout_rejects_a_promotion_before_its_start_time(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 10:00:00'));
        $product = Product::factory()->create();
        Promotion::query()->create([
            'code' => 'BELUMMULAI',
            'name' => 'Voucher belum mulai',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'starts_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $checkoutKey = (string) Str::uuid();

        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $checkoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $checkoutKey,
                'promotion_code' => 'BELUMMULAI',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_an_expired_promotion(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 10:00:00'));
        $product = Product::factory()->create();
        Promotion::query()->create([
            'code' => 'SUDAHEXPIRED',
            'name' => 'Voucher kadaluarsa',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'ends_at' => now()->subMinute(),
            'is_active' => true,
        ]);
        $checkoutKey = (string) Str::uuid();

        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $checkoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $checkoutKey,
                'promotion_code' => 'SUDAHEXPIRED',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_cannot_create_a_percentage_promotion_above_one_hundred_percent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.promotions.index'))
            ->post(route('admin.promotions.store'), [
                'code' => 'TOOBIG',
                'name' => 'Diskon tidak valid',
                'discount_type' => 'percentage',
                'discount_value' => 100.01,
                'minimum_order' => 0,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.promotions.index'))
            ->assertSessionHasErrors('discount_value');

        $this->assertDatabaseCount('promotions', 0);
    }

    public function test_checkout_rejects_points_without_a_customer_phone(): void
    {
        $product = Product::factory()->create(['price' => 20000]);
        $checkoutKey = (string) Str::uuid();

        $this->from(route('cart.index'))
            ->withSession([
                'cart' => [$product->id => 1],
                'checkout_key' => $checkoutKey,
            ])
            ->post(route('orders.store'), [
                'checkout_key' => $checkoutKey,
                'redeem_points' => 1,
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('redeem_points');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_admin_can_apply_a_promotion_and_loyalty_points_to_a_walk_in_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Dina',
            'phone' => '081288899900',
        ]);
        $customer->forceFill(['loyalty_points' => 100])->save();
        $product = Product::factory()->create(['price' => 20000]);
        $promotion = Promotion::query()->create([
            'code' => 'WALKIN5',
            'name' => 'Voucher walk-in',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'minimum_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('staff.orders.index'))
            ->assertOk()
            ->assertSee('Kode voucher (opsional)')
            ->assertSee('Gunakan 100 poin = Rp10.000');

        $this->actingAs($admin)
            ->post(route('staff.orders.manual.store'), [
                'customer_name' => 'Dina',
                'customer_phone' => $customer->phone,
                'promotion_code' => $promotion->code,
                'redeem_points' => 1,
                'items' => [$product->id => 1],
            ])
            ->assertRedirect(route('staff.orders.index'));

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'subtotal' => '20000.00',
            'promotion_id' => $promotion->id,
            'discount_amount' => '15000.00',
            'loyalty_points_redeemed' => 100,
            'total' => '5000.00',
        ]);
        $this->assertSame(0, $customer->fresh()->loyalty_points);
        $this->assertSame(1, $promotion->fresh()->usage_count);
    }

    public function test_non_admin_cannot_manage_promotions(): void
    {
        $nonAdmin = User::factory()->create(['role' => 'barista']);

        $this->actingAs($nonAdmin)
            ->get(route('admin.promotions.index'))
            ->assertForbidden();

        $this->assertDatabaseCount('promotions', 0);
    }
}
