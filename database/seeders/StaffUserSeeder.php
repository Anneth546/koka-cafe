<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $demoStaff = [
            ['name' => 'Admin KOKA', 'email' => 'admin@koka.test', 'role' => 'admin'],
        ];

        foreach ($demoStaff as $staff) {
            User::updateOrCreate(
                ['email' => $staff['email']],
                $staff + ['password' => 'KokaLocal2026!']
            );
        }
    }
}
