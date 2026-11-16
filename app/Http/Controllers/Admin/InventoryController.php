<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $selectedProductId = $request->integer('product_id');
        $selectedProduct = $selectedProductId > 0
            ? Product::query()->with('ingredients')->find($selectedProductId)
            : null;

        return view('admin.inventory.index', [
            'activeIngredientCount' => Ingredient::query()->where('is_active', true)->count(),
            'ingredients' => Ingredient::query()
                ->with('supplier')
                ->orderBy('name')
                ->paginate(15, ['*'], 'ingredients_page')
                ->withQueryString(),
            'lowStockCount' => Ingredient::query()
                ->where('is_active', true)
                ->whereColumn('stock_on_hand', '<=', 'low_stock_threshold')
                ->count(),
            'movements' => InventoryMovement::query()
                ->with(['ingredient', 'supplier', 'order'])
                ->latest()
                ->paginate(15, ['*'], 'movements_page')
                ->withQueryString(),
            'products' => Product::query()
                ->with('category')
                ->orderBy('name')
                ->get(),
            'recipeIngredients' => Ingredient::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'selectedProduct' => $selectedProduct,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function storeIngredient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:16'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama bahan wajib diisi.',
            'unit.required' => 'Satuan bahan wajib diisi.',
            'low_stock_threshold.required' => 'Batas stok minimum wajib diisi.',
        ]);

        Ingredient::create($validated);

        return to_route('admin.inventory.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function updateIngredient(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:16'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validated['unit'] !== $ingredient->unit && $ingredient->products()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'Satuan tidak dapat diubah selama bahan masih digunakan dalam resep produk.',
            ]);
        }

        $ingredient->update($validated);

        return to_route('admin.inventory.index')->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Nama pemasok wajib diisi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon.',
        ]);

        Supplier::create([...$validated, 'is_active' => true]);

        return to_route('admin.inventory.index')->with('success', 'Pemasok berhasil ditambahkan.');
    }

    public function updateSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $supplier->update($validated);

        return to_route('admin.inventory.index')->with('success', 'Data pemasok berhasil diperbarui.');
    }

    public function storeMovement(Request $request, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'type' => ['required', 'string', 'in:purchase,waste,adjustment'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'ingredient_id.required' => 'Pilih bahan yang akan dicatat.',
            'type.required' => 'Pilih jenis pergerakan stok.',
            'quantity.required' => 'Jumlah stok wajib diisi.',
        ]);

        if ($validated['type'] !== 'adjustment' && (float) $validated['quantity'] <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah stok masuk atau terbuang harus lebih dari nol.',
            ]);
        }

        if ($validated['type'] !== 'purchase') {
            unset($validated['supplier_id'], $validated['unit_cost']);
        }

        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $inventory->recordManualMovement($validated, $user->id);

        return to_route('admin.inventory.index')->with('success', 'Perubahan stok berhasil dicatat.');
    }
}
