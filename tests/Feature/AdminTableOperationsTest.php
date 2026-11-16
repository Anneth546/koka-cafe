<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_table_and_regenerate_its_qr_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.tables.store'), [
            'number' => 'T-12',
            'label' => 'Meja Jendela',
            'area' => 'Indoor',
            'capacity' => 4,
            'status' => 'available',
            'is_active' => 1,
        ])->assertRedirect(route('admin.tables.index'));

        $table = DiningTable::query()->where('number', 'T-12')->firstOrFail();
        $this->assertSame('Indoor', $table->area);
        $this->assertSame(4, $table->capacity);
        $originalToken = $table->qr_token;

        $this->get(route('admin.tables.index'))
            ->assertOk()
            ->assertSee('Meja Jendela')
            ->assertSee('Tambah meja');

        $this->post(route('admin.tables.qr.regenerate', $table))
            ->assertRedirect(route('admin.tables.index'));

        $this->assertNotSame($originalToken, $table->fresh()->qr_token);
    }

    public function test_admin_can_open_reservation_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DiningTable::factory()->create([
            'number' => 'R-1',
            'label' => 'Meja R-1',
            'capacity' => 4,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Tambah reservasi')
            ->assertSee('Meja R-1');
    }

    public function test_admin_can_update_table_capacity_area_and_operational_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table = DiningTable::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.tables.update', $table), [
                'number' => $table->number,
                'label' => 'Area Teras',
                'area' => 'Outdoor',
                'capacity' => 6,
                'status' => 'occupied',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.tables.index'));

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'label' => 'Area Teras',
            'area' => 'Outdoor',
            'capacity' => 6,
            'status' => 'occupied',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_reservations_and_overlapping_or_oversized_requests_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table = DiningTable::factory()->create(['capacity' => 4, 'is_active' => true]);
        $this->actingAs($admin);

        $reservationData = [
            'customer_name' => 'Nadia',
            'customer_phone' => '081234567890',
            'dining_table_id' => $table->id,
            'party_size' => 3,
            'starts_at' => now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'),
            'notes' => 'Dekat jendela',
        ];

        $this->post(route('admin.reservations.store'), $reservationData)
            ->assertRedirect(route('admin.reservations.index'));

        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame('confirmed', $reservation->status);

        $this->from(route('admin.reservations.index'))
            ->post(route('admin.reservations.store'), array_merge($reservationData, [
                'starts_at' => now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->setTime(15, 0)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('admin.reservations.index'))
            ->assertSessionHasErrors('dining_table_id');

        $this->from(route('admin.reservations.index'))
            ->post(route('admin.reservations.store'), array_merge($reservationData, [
                'party_size' => 5,
                'starts_at' => now()->addDay()->setTime(16, 0)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->setTime(18, 0)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('admin.reservations.index'))
            ->assertSessionHasErrors('party_size');

        $this->patch(route('admin.reservations.update-status', $reservation), [
            'status' => 'seated',
        ])->assertRedirect(route('admin.reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'seated',
        ]);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_non_admin_cannot_access_reservation_management(): void
    {
        $nonAdmin = User::factory()->create(['role' => 'barista']);

        $this->actingAs($nonAdmin)
            ->get(route('admin.reservations.index'))
            ->assertForbidden();
    }
}
