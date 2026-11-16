<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * @param array{
     *     dining_table_id: int|string,
     *     customer_name: string,
     *     customer_phone: string,
     *     party_size: int|string,
     *     starts_at: string,
     *     ends_at: string,
     *     notes?: string|null
     * } $attributes
     */
    public function create(array $attributes): Reservation
    {
        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $attributes['starts_at']);
        $endsAt = Carbon::createFromFormat('Y-m-d\TH:i', $attributes['ends_at']);

        return DB::transaction(function () use ($attributes, $startsAt, $endsAt): Reservation {
            $table = DiningTable::query()
                ->whereKey($attributes['dining_table_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $table) {
                throw ValidationException::withMessages([
                    'dining_table_id' => 'Meja tidak aktif. Pilih meja yang masih tersedia.',
                ]);
            }

            if ($attributes['party_size'] > $table->capacity) {
                throw ValidationException::withMessages([
                    'party_size' => 'Jumlah tamu melebihi kapasitas meja.',
                ]);
            }

            $hasConflict = Reservation::query()
                ->where('dining_table_id', $table->id)
                ->whereIn('status', ['confirmed', 'seated'])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'dining_table_id' => 'Meja sudah memiliki reservasi pada rentang waktu tersebut.',
                ]);
            }

            return Reservation::create([
                'dining_table_id' => $table->id,
                'customer_name' => $attributes['customer_name'],
                'customer_phone' => $attributes['customer_phone'],
                'party_size' => $attributes['party_size'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }
}
