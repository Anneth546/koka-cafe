<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_navigation_hides_category_links_and_shows_reservations(): void
    {
        $response = $this->get(route('customer.home'))->assertOk();
        $navigationMatched = preg_match('/<nav class="main-nav"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $navigation);

        $this->assertSame(1, $navigationMatched);
        $this->assertStringContainsString('Reservasi meja', $navigation[1]);
        $this->assertStringNotContainsString('kategori=', $navigation[1]);
        $response->assertSee('Semua menu');
    }

    public function test_customer_can_view_reservation_form_with_active_tables(): void
    {
        $activeTable = DiningTable::factory()->create([
            'number' => 'R-1',
            'label' => 'Meja Jendela',
            'capacity' => 4,
            'is_active' => true,
        ]);
        DiningTable::factory()->create([
            'number' => 'R-2',
            'label' => 'Meja Nonaktif',
            'is_active' => false,
        ]);

        $this->get(route('customer.reservations.create'))
            ->assertOk()
            ->assertSee('Reservasi meja')
            ->assertSee('Meja Jendela')
            ->assertDontSee('Meja Nonaktif');
    }

    public function test_customer_reservation_is_saved_and_visible_to_admin(): void
    {
        $table = DiningTable::factory()->create([
            'label' => 'Meja Jendela',
            'capacity' => 4,
            'is_active' => true,
        ]);
        $reservationData = [
            'customer_name' => 'Nadia',
            'customer_phone' => '081234567890',
            'dining_table_id' => $table->id,
            'party_size' => 3,
            'starts_at' => now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'),
            'notes' => 'Dekat jendela',
        ];

        $this->post(route('customer.reservations.store'), $reservationData)
            ->assertRedirect(route('customer.reservations.create'))
            ->assertSessionHas('success', 'Reservasi berhasil dicatat dan sudah masuk ke admin KOKA CAFE.');

        $this->assertDatabaseHas('reservations', [
            'dining_table_id' => $table->id,
            'customer_name' => 'Nadia',
            'customer_phone' => '081234567890',
            'party_size' => 3,
            'status' => 'confirmed',
            'notes' => 'Dekat jendela',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Nadia')
            ->assertSee('Meja Jendela')
            ->assertSee('Dekat jendela');
    }

    public function test_customer_cannot_reserve_a_table_above_its_capacity(): void
    {
        $table = DiningTable::factory()->create([
            'capacity' => 2,
            'is_active' => true,
        ]);
        $reservationData = [
            'customer_name' => 'Nadia',
            'customer_phone' => '081234567890',
            'dining_table_id' => $table->id,
            'party_size' => 3,
            'starts_at' => now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'),
        ];

        $this->from(route('customer.reservations.create'))
            ->post(route('customer.reservations.store'), $reservationData)
            ->assertRedirect(route('customer.reservations.create'))
            ->assertSessionHasErrors([
                'party_size' => 'Jumlah tamu melebihi kapasitas meja.',
            ]);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_customer_cannot_reserve_an_inactive_table(): void
    {
        $table = DiningTable::factory()->create([
            'is_active' => false,
        ]);
        $reservationData = [
            'customer_name' => 'Nadia',
            'customer_phone' => '081234567890',
            'dining_table_id' => $table->id,
            'party_size' => 2,
            'starts_at' => now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'),
        ];

        $this->from(route('customer.reservations.create'))
            ->post(route('customer.reservations.store'), $reservationData)
            ->assertRedirect(route('customer.reservations.create'))
            ->assertSessionHasErrors([
                'dining_table_id' => 'Meja tidak aktif. Pilih meja yang masih tersedia.',
            ]);

        $this->assertDatabaseCount('reservations', 0);
    }
}
