<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const STATUS_LABELS = [
        'pending_payment' => 'Menunggu pembayaran',
        'paid' => 'Pembayaran dikonfirmasi',
        'preparing' => 'Sedang disiapkan',
        'ready' => 'Siap disajikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    private const ACTION_LABELS = [
        'paid' => 'Konfirmasi pembayaran',
        'preparing' => 'Mulai menyiapkan',
        'ready' => 'Tandai siap',
        'completed' => 'Tandai selesai',
    ];

    private const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'debit' => 'Kartu debit',
        'transfer' => 'Transfer',
        'ewallet' => 'E-wallet',
    ];

    private const TRANSITIONS = [
        'admin' => [
            'pending_payment' => 'paid',
            'paid' => 'preparing',
            'preparing' => 'ready',
            'ready' => 'completed',
        ],
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user() instanceof User, 403);

        $staffRole = $request->user()->role;
        $isQueue = $request->boolean('queue');
        $visibleStatuses = $isQueue
            ? ['paid', 'preparing', 'ready']
            : array_keys(self::STATUS_LABELS);
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in($visibleStatuses)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'order_number' => ['nullable', 'string', 'max:24'],
        ]);
        $selectedStatus = $validated['status'] ?? null;

        $orders = Order::query()
            ->with(['items', 'diningTable'])
            ->whereIn('status', $visibleStatuses)
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($validated['table_id'] ?? null, fn ($query, $tableId) => $query->where('dining_table_id', $tableId))
            ->when($validated['order_number'] ?? null, fn ($query, $orderNumber) => $query->where(
                'order_number',
                'like',
                '%'.$orderNumber.'%'
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Order::query()
            ->whereIn('status', $visibleStatuses)
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('staff.orders.index', [
            'actionLabels' => self::ACTION_LABELS,
            'allowedTransitions' => self::TRANSITIONS[$staffRole],
            'isQueue' => $isQueue,
            'orders' => $orders,
            'paymentMethods' => self::PAYMENT_METHODS,
            'selectedStatus' => $selectedStatus,
            'tables' => DiningTable::query()->orderBy('number')->get(),
            'availableProducts' => Product::query()
                ->availableForSale()
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'statusCounts' => $statusCounts,
            'statusLabels' => array_intersect_key(self::STATUS_LABELS, array_flip($visibleStatuses)),
        ]);
    }

    public function updateStatus(
        Request $request,
        Order $order,
        InventoryService $inventory
    ): RedirectResponse {
        abort_unless($request->user() instanceof User, 403);

        $staffRole = $request->user()->role;
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(self::ACTION_LABELS))],
            'payment_method' => [
                'nullable',
                'required_if:status,paid',
                Rule::in(array_keys(self::PAYMENT_METHODS)),
            ],
            'amount_received' => ['nullable', 'required_if:payment_method,cash', 'numeric', 'min:0'],
        ], [
            'status.required' => 'Pilih tindakan untuk pesanan.',
            'status.in' => 'Tindakan pesanan tidak valid.',
            'payment_method.required_if' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'amount_received.required_if' => 'Masukkan jumlah uang yang diterima.',
            'amount_received.numeric' => 'Jumlah uang yang diterima harus berupa angka.',
            'amount_received.min' => 'Jumlah uang yang diterima tidak boleh negatif.',
        ]);
        $targetStatus = $validated['status'];
        $allowedTargets = array_values(self::TRANSITIONS[$staffRole] ?? []);

        abort_unless(in_array($targetStatus, $allowedTargets, true), 403);
        $changedBy = $request->user()->id;

        DB::transaction(function () use (
            $order,
            $staffRole,
            $targetStatus,
            $validated,
            $inventory,
            $changedBy
        ): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $nextStatus = self::TRANSITIONS[$staffRole][$lockedOrder->status] ?? null;

            if ($nextStatus !== $targetStatus) {
                throw ValidationException::withMessages([
                    'status' => 'Status pesanan sudah berubah atau tindakan tidak sesuai alur. Muat ulang halaman.',
                ]);
            }

            $updates = ['status' => $targetStatus];

            if ($targetStatus === 'paid') {
                $totalCents = self::toCents((string) $lockedOrder->total);
                $paymentMethod = $validated['payment_method'];
                $amountReceivedCents = $paymentMethod === 'cash'
                    ? self::toCents((string) $validated['amount_received'])
                    : $totalCents;

                if ($amountReceivedCents < $totalCents) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Uang yang diterima kurang dari total pesanan.',
                    ]);
                }

                $updates += [
                    'payment_method' => $paymentMethod,
                    'amount_received' => self::formatCents($amountReceivedCents),
                    'change_due' => self::formatCents($amountReceivedCents - $totalCents),
                    'paid_at' => now(),
                ];
                $inventory->consumeOrder($lockedOrder, $changedBy);

                $pointsEarned = intdiv($totalCents, 1000000);
                $customer = $lockedOrder->customer_id
                    ? Customer::query()->lockForUpdate()->find($lockedOrder->customer_id)
                    : null;

                if ($customer && $pointsEarned > 0) {
                    $customer->increment('loyalty_points', $pointsEarned);
                }

                $updates['loyalty_points_earned'] = $pointsEarned;
            }

            $lockedOrder->update($updates);
        });

        return to_route('staff.orders.index', $request->boolean('queue') ? ['queue' => 1] : [])->with(
            'success',
            'Pesanan '.$order->order_number.' diperbarui: '.self::STATUS_LABELS[$targetStatus].'.'
        );
    }

    public function cancel(Request $request, Order $order, OrderPricingService $pricingService): RedirectResponse
    {
        abort_unless($request->user() instanceof User, 403);

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_reason.max' => 'Alasan pembatalan maksimal 1.000 karakter.',
        ]);

        DB::transaction(function () use ($order, $validated, $pricingService): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status !== 'pending_payment') {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'Hanya pesanan yang belum dibayar yang dapat dibatalkan dari halaman ini.',
                ]);
            }

            if ($lockedOrder->customer_id && $lockedOrder->loyalty_points_redeemed > 0) {
                $customer = Customer::query()
                    ->lockForUpdate()
                    ->find($lockedOrder->customer_id);

                $customer?->increment('loyalty_points', $lockedOrder->loyalty_points_redeemed);
            }

            $pricingService->releasePromotionUsage($lockedOrder);

            $lockedOrder->update([
                'status' => 'cancelled',
                'cancel_reason' => $validated['cancel_reason'],
                'cancelled_at' => now(),
            ]);
        });

        return to_route('staff.orders.index')->with('success', 'Pesanan '.$order->order_number.' dibatalkan.');
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
