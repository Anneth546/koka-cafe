<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_saves_database_prices_and_order_items(): void
    {
        $table = DiningTable::factory()->create(['number' => '11']);
        $product = Product::factory()->create(['price' => 45000]);
        $checkoutKey = (string) Str::uuid();
        $csrfToken = 'checkout-test-token';

        $this->withSession([
            '_token' => $csrfToken,
            'cart' => [$product->id => 2],
            'checkout_key' => $checkoutKey,
            'dining_table_id' => $table->id,
        ])->post('/pesanan', [
            '_token' => $csrfToken,
            'checkout_key' => $checkoutKey,
            'customer_name' => 'Ayu',
            'notes' => 'Kurangi gula',
            'total' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'dining_table_id' => $table->id,
            'customer_name' => 'Ayu',
            'status' => 'pending_payment',
            'subtotal' => '90000.00',
            'total' => '90000.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => '45000.00',
            'quantity' => 2,
            'line_total' => '90000.00',
        ]);
    }

    public function test_repeating_a_checkout_request_does_not_create_a_duplicate_order(): void
    {
        $table = DiningTable::factory()->create(['number' => '12']);
        $product = Product::factory()->create();
        $checkoutKey = (string) Str::uuid();
        $csrfToken = 'retry-test-token';
        $session = [
            '_token' => $csrfToken,
            'cart' => [$product->id => 1],
            'checkout_key' => $checkoutKey,
            'dining_table_id' => $table->id,
        ];
        $payload = ['_token' => $csrfToken, 'checkout_key' => $checkoutKey];

        $this->withSession($session)->post('/pesanan', $payload)->assertRedirect();
        $order = Order::query()->firstOrFail();

        $this->withSession($session)->post('/pesanan', $payload)
            ->assertRedirect('/pesanan/'.$order->public_token);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_checkout_with_an_inactive_table_does_not_create_an_order(): void
    {
        $table = DiningTable::factory()->create(['is_active' => false]);
        $product = Product::factory()->create();
        $checkoutKey = (string) Str::uuid();
        $csrfToken = 'table-required-token';

        $this->withSession([
            '_token' => $csrfToken,
            'cart' => [$product->id => 1],
            'checkout_key' => $checkoutKey,
            'dining_table_id' => $table->id,
        ])->post('/pesanan', [
            '_token' => $csrfToken,
            'checkout_key' => $checkoutKey,
        ])->assertSessionHasErrors('meja');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_can_scan_a_table_order_and_reach_the_order_status_page(): void
    {
        $table = DiningTable::factory()->create([
            'number' => '13',
            'label' => 'Meja Pojok',
        ]);
        $product = Product::factory()->create([
            'name' => 'Iced Koka Latte',
            'price' => 32000,
        ]);
        $csrfToken = 'guest-order-flow-token';

        $this->get('/meja/'.$table->qr_token)
            ->assertRedirect('/menu')
            ->assertSessionHas('dining_table_id', $table->id);

        $this->withSession(['_token' => $csrfToken])
            ->post('/keranjang/items', [
                '_token' => $csrfToken,
                'product_id' => $product->id,
            ])
            ->assertRedirect('/keranjang');

        $this->get('/keranjang')
            ->assertOk()
            ->assertSee('Kirim pesanan')
            ->assertSee('Kode voucher (opsional)')
            ->assertSee('100 poin = Rp10.000')
            ->assertSee('Meja Pojok');

        $checkoutKey = (string) session('checkout_key');

        $checkoutResponse = $this->withSession(['_token' => $csrfToken])
            ->post('/pesanan', [
                '_token' => $csrfToken,
                'checkout_key' => $checkoutKey,
                'customer_name' => 'Nia',
                'notes' => 'Tanpa es batu',
            ]);

        $order = Order::query()->firstOrFail();
        $checkoutResponse->assertRedirect('/pesanan/'.$order->public_token);

        $this->get('/pesanan/'.$order->public_token)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Meja Pojok')
            ->assertSee('Rp 32.000');

        $this->get('/keranjang')->assertSee('Belum ada yang dipilih.');
        $this->assertDatabaseCount('orders', 1);
        $this->assertGuest();
    }

    public function test_guest_can_place_an_order_without_scanning_a_table_qr(): void
    {
        $product = Product::factory()->create([
            'name' => 'Iced Koka Latte',
            'price' => 32000,
        ]);
        $csrfToken = 'direct-order-token';

        $this->withSession([
            '_token' => $csrfToken,
            'cart' => [$product->id => 1],
        ])->get('/keranjang')
            ->assertOk()
            ->assertSee('Anda bisa langsung memesan tanpa QR meja.')
            ->assertSee('Kirim pesanan');

        $checkoutKey = (string) session('checkout_key');

        $checkoutResponse = $this->withSession(['_token' => $csrfToken])
            ->post('/pesanan', [
                '_token' => $csrfToken,
                'checkout_key' => $checkoutKey,
                'customer_name' => 'Nia',
            ]);

        $order = Order::query()->firstOrFail();
        $checkoutResponse->assertRedirect('/pesanan/'.$order->public_token);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'dining_table_id' => null,
            'customer_name' => 'Nia',
            'status' => 'pending_payment',
        ]);

        $this->get('/pesanan/'.$order->public_token)
            ->assertOk()
            ->assertSee('Pesanan tanpa meja')
            ->assertSee($order->order_number);

        $this->get('/keranjang')->assertSee('Belum ada yang dipilih.');
    }
}
