<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email'];

    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function paidOrders(): HasMany
    {
        return $this->orders()->whereNotNull('paid_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
