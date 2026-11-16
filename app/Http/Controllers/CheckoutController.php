<?php

namespace App\Http\Controllers;

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
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        InventoryService $inventory,
        CustomerService $customers,
        OrderPricingService $pricingService
    ): RedirectResponse {
        $validated = $request->validate([
            'checkout_key' => ['required', 'uuid'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promotion_code' => ['nullable', 'string', 'max:40'],
            'redeem_points' => ['sometimes', 'boolean'],
        ], [
            'checkout_key.required' => 'Sesi checkout tidak valid. Muat ulang keranjang Anda.',
            'customer_name.max' => 'Nama maksimal 100 karakter.',
            'customer_phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon.',
            'notes.max' => 'Catatan maksimal 1.000 karakter.',
            'promotion_code.max' => 'Kode voucher maksimal 40 karakter.',
            'redeem_points.boolean' => 'Pilihan penukaran poin tidak valid.',
        ]);

        $sessionCheckoutKey = (string) session('checkout_key', '');
        abort_unless($sessionCheckoutKey !== '' && hash_equals($sessionCheckoutKey, $validated['checkout_key']), 419);

        $existingOrder = Order::query()
            ->where('idempotency_key', $validated['checkout_key'])
            ->first();

        if ($existingOrder) {
            return to_route('orders.show', $existingOrder->public_token);
        }

        $cart = collect(session('cart', []))
            ->mapWithKeys(fn ($quantity, $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity >= 1 && $quantity <= 99)
            ->all();

        if ($cart === []) {
            return to_route('cart.index')->withErrors(['keranjang' => 'Keranjang Anda masih kosong.']);
        }

        $diningTableId = session('dining_table_id');

        $order = DB::transaction(function () use (
            $cart,
            $diningTableId,
            $validated,
            $inventory,
            $customers,
            $pricingService
        ): Order {
            $table = $diningTableId
                ? DiningTable::query()
                    ->whereKey($diningTableId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($diningTableId && ! $table) {
                throw ValidationException::withMessages([
                    'meja' => 'Meja tidak aktif. Pindai QR meja yang masih berlaku.',
                ]);
            }

            $existingOrder = Order::query()
                ->where('idempotency_key', $validated['checkout_key'])
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }

            $products = Product::query()
                ->whereKey(array_keys($cart))
                ->availableForSale()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages([
                    'keranjang' => 'Ada produk yang sudah tidak tersedia. Perbarui keranjang Anda.',
                ]);
            }
            $inventory->assertStockAvailable($cart);
            $subtotalCents = $products->sum(function (Product $product) use ($cart): int {
                $unitPriceCents = (int) round((float) $product->price * 100);

                return $unitPriceCents * $cart[$product->id];
            });
            $customer = $customers->findOrCreateFromOrder(
                $validated['customer_name'] ?? null,
                $validated['customer_phone'] ?? null
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
                'idempotency_key' => $validated['checkout_key'],
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
                $quantity = $cart[$product->id];
                $unitPriceCents = (int) round((float) $product->price * 100);

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

        session()->forget('cart');

        return to_route('orders.show', $order->public_token);
    }

    public function show(string $token): View
    {
        $order = Order::query()
            ->with(['items', 'diningTable', 'review'])
            ->where('public_token', $token)
            ->firstOrFail();

        $statusLabels = [
            'pending_payment' => 'Menunggu pembayaran',
            'paid' => 'Dibayar',
            'preparing' => 'Sedang disiapkan',
            'ready' => 'Siap disajikan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
        $statusMessages = [
            'pending_payment' => 'Tunjukkan nomor pesanan ini kepada kasir untuk melakukan pembayaran. Setelah pembayaran dikonfirmasi, pesanan diteruskan ke barista.',
            'paid' => 'Pembayaran sudah dikonfirmasi. Pesanan akan segera disiapkan oleh barista.',
            'preparing' => 'Pesanan sedang disiapkan oleh barista.',
            'ready' => 'Pesanan sudah siap. Tunjukkan nomor ini kepada staf untuk mengambil pesanan atau menunggu disajikan di meja.',
            'completed' => 'Pesanan telah selesai. Terima kasih sudah memesan di KOKA CAFE.',
            'cancelled' => 'Pesanan dibatalkan. Silakan hubungi kasir jika Anda memerlukan bantuan.',
        ];

        return view('customer.order-status', compact('order', 'statusLabels', 'statusMessages'));
    }

    private static function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
