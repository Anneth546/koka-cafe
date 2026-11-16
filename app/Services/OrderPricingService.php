<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use Illuminate\Validation\ValidationException;

class OrderPricingService
{
    public function __construct(private PromotionService $promotions) {}

    /**
     * @return array{
     *     promotion: ?Promotion,
     *     promotion_discount_cents: int,
     *     total_discount_cents: int,
     *     loyalty_points_redeemed: int,
     *     total_cents: int,
     *     customer: ?Customer
     * }
     */
    public function price(int $subtotalCents, ?string $promotionCode, bool $redeemPoints, ?Customer $customer): array
    {
        $promotionPricing = $this->promotions->resolve($promotionCode, $subtotalCents);
        $promotionDiscountCents = $promotionPricing['discount_cents'];
        $customerToUpdate = $customer;
        $pointsToRedeem = 0;
        $loyaltyDiscountCents = 0;

        if ($redeemPoints) {
            if (! $customer) {
                throw ValidationException::withMessages([
                    'redeem_points' => 'Masukkan nomor HP agar poin loyalitas dapat digunakan.',
                ]);
            }

            $customerToUpdate = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $remainingCents = $subtotalCents - $promotionDiscountCents;
            $availablePoints = intdiv($customerToUpdate->loyalty_points, 100) * 100;
            $affordablePoints = intdiv($remainingCents, 1000000) * 100;
            $pointsToRedeem = min($availablePoints, $affordablePoints);

            if ($pointsToRedeem < 100) {
                throw ValidationException::withMessages([
                    'redeem_points' => 'Poin belum cukup atau total pesanan terlalu kecil untuk penukaran.',
                ]);
            }

            $loyaltyDiscountCents = intdiv($pointsToRedeem, 100) * 1000000;
            $customerToUpdate->decrement('loyalty_points', $pointsToRedeem);
        }

        $totalDiscountCents = $promotionDiscountCents + $loyaltyDiscountCents;

        return [
            'promotion' => $promotionPricing['promotion'],
            'promotion_discount_cents' => $promotionDiscountCents,
            'total_discount_cents' => $totalDiscountCents,
            'loyalty_points_redeemed' => $pointsToRedeem,
            'total_cents' => $subtotalCents - $totalDiscountCents,
            'customer' => $customerToUpdate,
        ];
    }

    public function recordPromotionUsage(Order $order, ?Customer $customer, int $discountCents): void
    {
        if (! $order->promotion_id) {
            return;
        }

        PromotionUsage::create([
            'promotion_id' => $order->promotion_id,
            'order_id' => $order->id,
            'customer_id' => $customer?->id,
            'discount_amount' => self::formatCents($discountCents),
        ]);

        Promotion::query()->whereKey($order->promotion_id)->increment('usage_count');
    }

    public function releasePromotionUsage(Order $order): void
    {
        $usage = PromotionUsage::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (! $usage) {
            return;
        }

        $promotion = Promotion::query()
            ->whereKey($usage->promotion_id)
            ->lockForUpdate()
            ->firstOrFail();

        $usage->delete();
        $promotion->decrement('usage_count');
    }

    private static function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
