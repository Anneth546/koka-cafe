<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_admin_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/staff/login');
    }

    public function test_non_admin_cannot_open_admin_dashboard_or_sales_report(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($owner)
            ->get('/admin/reports/sales')
            ->assertForbidden();
    }

    public function test_admin_dashboard_shows_today_sales_and_active_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Order::factory()->create([
            'status' => 'paid',
            'total' => 25000,
            'paid_at' => now(),
        ]);
        Order::factory()->create(['status' => 'pending_payment']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard admin.')
            ->assertSee('Rp 25.000')
            ->assertSee('Laporan penjualan')
            ->assertSee('Pesanan aktif');
    }

    public function test_sales_report_only_includes_paid_orders_in_the_selected_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $today = now();
        $paidOrder = Order::factory()->create([
            'status' => 'completed',
            'subtotal' => 30000,
            'discount_amount' => 5000,
            'total' => 25000,
            'payment_method' => 'qris',
            'paid_at' => $today,
        ]);
        OrderItem::create([
            'order_id' => $paidOrder->id,
            'product_id' => null,
            'product_name' => 'Koka Latte',
            'unit_price' => 25000,
            'quantity' => 1,
            'line_total' => 25000,
        ]);
        Order::factory()->create([
            'status' => 'completed',
            'total' => 12000,
            'payment_method' => 'cash',
            'paid_at' => $today->copy()->subDay(),
        ]);
        Order::factory()->create([
            'status' => 'pending_payment',
            'total' => 40000,
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports/sales?from='.$today->toDateString().'&to='.$today->toDateString())
            ->assertOk()
            ->assertSee('Rp 25.000')
            ->assertSee('Rp 5.000')
            ->assertSee('Total diskon')
            ->assertSee('QRIS')
            ->assertSee('Koka Latte')
            ->assertDontSee('Tunai');
    }
}
