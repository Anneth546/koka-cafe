<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function findOrCreateFromOrder(?string $name, ?string $phone): ?Customer
    {
        if (blank($phone)) {
            return null;
        }

        $customer = Customer::query()->firstOrCreate(
            ['phone' => $phone],
            ['name' => filled($name) ? $name : 'Pelanggan']
        );

        if (filled($name) && $customer->name !== $name) {
            $customer->update(['name' => $name]);
        }

        return $customer;
    }
}
