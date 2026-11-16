<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * @return array{promotion: ?Promotion, discount_cents: int}
     */
    public function resolve(?string $code, int $subtotalCents): array
    {
        if (blank($code)) {
            return ['promotion' => null, 'discount_cents' => 0];
        }

        $promotion = Promotion::query()
            ->where('code', Str::upper(trim($code)))
            ->lockForUpdate()
            ->first();

        if (! $promotion || ! $promotion->is_active) {
            throw ValidationException::withMessages([
                'promotion_code' => 'Kode voucher tidak ditemukan atau sedang tidak aktif.',
            ]);
        }

        if (($promotion->starts_at && $promotion->starts_at->isFuture())
            || ($promotion->ends_at && $promotion->ends_at->isPast())) {
            throw ValidationException::withMessages([
                'promotion_code' => 'Voucher belum berlaku atau masa berlakunya sudah habis.',
            ]);
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            throw ValidationException::withMessages([
                'promotion_code' => 'Kuota voucher sudah habis.',
            ]);
        }

        $minimumOrderCents = self::toCents((string) $promotion->minimum_order);

        if ($subtotalCents < $minimumOrderCents) {
            throw ValidationException::withMessages([
                'promotion_code' => 'Total belanja belum mencapai minimum voucher.',
            ]);
        }

        $discountCents = $promotion->discount_type === 'fixed'
            ? self::toCents((string) $promotion->discount_value)
            : self::percentageDiscount($subtotalCents, (string) $promotion->discount_value);

        if ($promotion->maximum_discount !== null) {
            $discountCents = min($discountCents, self::toCents((string) $promotion->maximum_discount));
        }

        return [
            'promotion' => $promotion,
            'discount_cents' => min($subtotalCents, $discountCents),
        ];
    }

    private static function percentageDiscount(int $subtotalCents, string $percentage): int
    {
        $basisPoints = (int) round((float) $percentage * 100);

        return intdiv(($subtotalCents * $basisPoints) + 5000, 10000);
    }

    private static function toCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
