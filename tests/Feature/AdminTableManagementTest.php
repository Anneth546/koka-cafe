<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTableManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_staff_login_from_table_admin(): void
    {
        $this->get('/admin/tables')
            ->assertRedirect('/staff/login');
    }

    public function test_customer_role_cannot_access_table_management(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/tables')
            ->assertForbidden();
    }

    public function test_owner_role_cannot_access_table_management(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->get('/admin/tables')
            ->assertForbidden();
    }

    public function test_admin_can_view_and_deactivate_a_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table = DiningTable::factory()->create([
            'number' => '31',
            'label' => 'Meja Jendela',
        ]);
        $csrfToken = 'admin-table-test-token';

        $this->actingAs($admin)
            ->get('/admin/tables')
            ->assertOk()
            ->assertSee('Meja Jendela')
            ->assertSee('Cetak QR meja');

        $this->withSession(['_token' => $csrfToken])
            ->actingAs($admin)
            ->patch('/admin/tables/'.$table->id, [
                '_token' => $csrfToken,
                'is_active' => 0,
            ])
            ->assertRedirect('/admin/tables');

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_open_the_protected_qr_print_sheet(): void
    {
        Storage::fake('local');
        config(['app.qr_base_url' => 'https://koka.example']);
        DiningTable::factory()->create([
            'number' => '32',
            'label' => 'Meja Depan',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/tables/qr?base_url=https://koka.example')
            ->assertOk()
            ->assertSee('Meja Depan')
            ->assertSee('data:image/svg+xml;base64,', false);
    }
}
