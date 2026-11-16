<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SOLD_ORDER_STATUSES = ['paid', 'preparing', 'ready', 'completed'];

    private const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'debit' => 'Kartu debit',
        'transfer' => 'Transfer',
        'ewallet' => 'E-wallet',
    ];

    public function index(): View
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $todayOrders = $this->paidOrdersBetween($todayStart, $todayEnd);

        return view('admin.dashboard', [
            'activeOrders' => Order::query()
                ->whereIn('status', ['pending_payment', 'paid', 'preparing', 'ready'])
                ->count(),
            'averageOrderValue' => (clone $todayOrders)->avg('total') ?? 0,
            'todayOrderCount' => (clone $todayOrders)->count(),
            'todaySales' => (clone $todayOrders)->sum('total'),
        ]);
    }

    public function salesReport(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.date_format' => 'Tanggal awal tidak valid.',
            'to.date_format' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau sesudah tanggal awal.',
        ]);

        $fromDate = $validated['from'] ?? today()->toDateString();
        $toDate = $validated['to'] ?? today()->toDateString();
        $from = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();
        $orders = $this->paidOrdersBetween($from, $to);

        $dailySales = (clone $orders)
            ->selectRaw('DATE(paid_at) as sales_date, SUM(total) as revenue, SUM(discount_amount) as discounts, COUNT(*) as order_count')
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('sales_date')
            ->get();

        $paymentBreakdown = (clone $orders)
            ->selectRaw('payment_method, SUM(total) as revenue, SUM(discount_amount) as discounts, COUNT(*) as order_count')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        $bestSellers = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->whereIn('orders.status', self::SOLD_ORDER_STATUSES)
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        return view('admin.reports.sales', [
            'averageOrderValue' => (clone $orders)->avg('total') ?? 0,
            'bestSellers' => $bestSellers,
            'discountTotal' => (clone $orders)->sum('discount_amount'),
            'fromDate' => $fromDate,
            'orderCount' => (clone $orders)->count(),
            'paymentBreakdown' => $paymentBreakdown,
            'paymentMethods' => self::PAYMENT_METHODS,
            'salesTotal' => (clone $orders)->sum('total'),
            'dailySales' => $dailySales,
            'toDate' => $toDate,
        ]);
    }

    private function paidOrdersBetween(Carbon $from, Carbon $to): Builder
    {
        return Order::query()
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->whereIn('status', self::SOLD_ORDER_STATUSES);
    }
}
