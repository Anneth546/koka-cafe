<?php

namespace Database\Factories;

use App\Models\DiningTable;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'KOKA-'.fake()->unique()->numerify('########'),
            'public_token' => Str::random(48),
            'idempotency_key' => (string) Str::uuid(),
            'dining_table_id' => DiningTable::factory(),
            'customer_name' => fake()->firstName(),
            'notes' => null,
            'status' => 'pending_payment',
            'subtotal' => 32000,
            'total' => 32000,
        ];
    }
}
