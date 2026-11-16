<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_is_redirected_to_order_management(): void
    {
        $staff = User::factory()->create([
            'email' => 'admin@koka.test',
            'password' => 'secret-password',
            'role' => 'admin',
        ]);
        $csrfToken = 'staff-login-test-token';

        $this->withSession(['_token' => $csrfToken])
            ->post('/staff/login', [
                '_token' => $csrfToken,
                'email' => $staff->email,
                'password' => 'secret-password',
            ])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($staff);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_account_cannot_sign_in_to_staff_area(string $role): void
    {
        $staff = User::factory()->create([
            'password' => 'staff-password',
            'role' => $role,
        ]);
        $csrfToken = 'staff-login-test-token';

        $this->from('/staff/login')
            ->withSession(['_token' => $csrfToken])
            ->post('/staff/login', [
                '_token' => $csrfToken,
                'email' => $staff->email,
                'password' => 'staff-password',
            ])
            ->assertRedirect('/staff/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public static function nonAdminRoles(): array
    {
        return [
            'owner' => ['owner'],
            'cashier' => ['cashier'],
            'barista' => ['barista'],
            'customer' => ['customer'],
        ];
    }
}
