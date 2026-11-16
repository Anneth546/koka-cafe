<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = collect(session('cart', []))
            ->mapWithKeys(fn ($quantity, $productId): array => [(int) $productId => (int) $quantity])
            ->all();

        $products = Product::query()
            ->with('category')
            ->availableForSale()
            ->whereKey(array_keys($cart))
            ->get()
            ->keyBy('id');

        $cart = array_intersect_key($cart, $products->all());
        session(['cart' => $cart]);

        $subtotal = $products->sum(fn (Product $product): float => (float) $product->price * $cart[$product->id]);
        $cartCount = array_sum($cart);
        $table = DiningTable::query()
            ->whereKey(session('dining_table_id'))
            ->where('is_active', true)
            ->first();
        $checkoutKey = session('checkout_key', (string) Str::uuid());
        session(['checkout_key' => $checkoutKey]);

        return view('customer.cart', compact('cart', 'cartCount', 'checkoutKey', 'products', 'subtotal', 'table'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'product_id.required' => 'Pilih produk yang ingin ditambahkan.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah minimal satu produk.',
            'quantity.max' => 'Jumlah maksimal 99 produk per item.',
        ]);

        $product = Product::query()
            ->whereKey($validated['product_id'])
            ->availableForSale()
            ->firstOrFail();

        $cart = session('cart', []);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart[$product->id] = min(99, (int) ($cart[$product->id] ?? 0) + $quantity);
        session(['cart' => $cart]);
        session()->forget('checkout_key');

        return to_route('cart.index')->with('success', $product->name.' ditambahkan ke keranjang.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'quantity.required' => 'Masukkan jumlah produk.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah minimal satu produk.',
            'quantity.max' => 'Jumlah maksimal 99 produk per item.',
        ]);

        $cart = session('cart', []);
        abort_unless(array_key_exists($product->id, $cart), 404);

        $cart[$product->id] = (int) $validated['quantity'];
        session(['cart' => $cart]);
        session()->forget('checkout_key');

        return to_route('cart.index')->with('success', 'Jumlah pesanan diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);
        session()->forget('checkout_key');

        return to_route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }
}
