<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Services\CustomerService;
use App\Services\InventoryService;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalkInOrderController extends Controller
{
    public function store(
        Request $request,
        InventoryService $inventory,
        CustomerService $customers,
        OrderPricingService $pricingService
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promotion_code' => ['nullable', 'string', 'max:40'],
            'redeem_points' => ['sometimes', 'boolean'],
            'items' => ['required', 'array'],
            'items.*' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'customer_phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon.',
            'dining_table_id.exists' => 'Meja yang dipilih tidak ditemukan.',
            'items.required' => 'Pilih setidaknya satu produk.',
            'items.*.integer' => 'Jumlah produk harus berupa angka bulat.',
            'items.*.max' => 'Jumlah tiap produk maksimal 99.',
        ]);

        $quantities = collect($validated['items'])
            ->mapWithKeys(fn (string|int $quantity, string|int $productId): array => [
                (int) $productId => (int) $quantity,
            ])
            ->filter(fn (int $quantity): bool => $quantity > 0);

        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Masukkan jumlah minimal satu produk.',
            ]);
        }

        $order = DB::transaction(function () use (
            $validated,
            $quantities,
            $inventory,
            $customers,
            $pricingService
        ): Order {
            $table = null;

            if (! empty($validated['dining_table_id'])) {
                $table = DiningTable::query()
                    ->whereKey($validated['dining_table_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $table) {
                    throw ValidationException::withMessages([
                        'dining_table_id' => 'Meja tidak aktif. Pilih meja yang masih tersedia.',
                    ]);
                }
            }

            $products = Product::query()
                ->whereKey($quantities->keys())
                ->availableForSale()
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk yang tidak tersedia. Perbarui pilihan produk.',
                ]);
            }
            $inventory->assertStockAvailable($quantities->all());
            $customer = $customers->findOrCreateFromOrder(
                $validated['customer_name'] ?? null,
                $validated['customer_phone'] ?? null
            );

            $subtotalCents = $products->sum(
                fn (Product $product): int => self::toCents((string) $product->price) * $quantities[$product->id]
            );
            $pricing = $pricingService->price(
                $subtotalCents,
                $validated['promotion_code'] ?? null,
                (bool) ($validated['redeem_points'] ?? false),
                $customer
            );
            $customer = $pricing['customer'];
            $promotion = $pricing['promotion'];

            $order = Order::create([
                'order_number' => 'KOKA-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'public_token' => Str::random(48),
                'idempotency_key' => (string) Str::uuid(),
                'dining_table_id' => $table?->id,
                'customer_id' => $customer?->id,
                'promotion_id' => $promotion?->id,
                'promotion_code' => $promotion?->code,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending_payment',
                'subtotal' => self::formatCents($subtotalCents),
                'discount_amount' => self::formatCents($pricing['total_discount_cents']),
                'loyalty_points_redeemed' => $pricing['loyalty_points_redeemed'],
                'total' => self::formatCents($pricing['total_cents']),
            ]);
            $pricingService->recordPromotionUsage($order, $customer, $pricing['promotion_discount_cents']);

            foreach ($products as $product) {
                $quantity = $quantities[$product->id];
                $unitPriceCents = self::toCents((string) $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => self::formatCents($unitPriceCents * $quantity),
                ]);
            }

            return $order;
        });

        return to_route('staff.orders.index')->with(
            'success',
            'Pesanan manual '.$order->order_number.' berhasil dibuat.'
        );
    }

    private static function toCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private static function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
