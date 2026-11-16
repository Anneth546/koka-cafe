<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 12) as $number) {
            $tableNumber = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            DiningTable::firstOrCreate(
                ['number' => $tableNumber],
                [
                    'label' => 'Meja '.$tableNumber,
                    'qr_token' => Str::random(48),
                    'is_active' => true,
                ]
            );
        }
    }
}
