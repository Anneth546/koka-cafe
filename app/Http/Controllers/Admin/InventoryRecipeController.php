<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryRecipeController extends Controller
{
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'ingredients' => ['nullable', 'array', 'max:100'],
            'ingredients.*' => ['nullable', 'numeric', 'gt:0', 'max:999999999.999'],
        ], [
            'ingredients.*.numeric' => 'Jumlah bahan resep harus berupa angka.',
            'ingredients.*.gt' => 'Jumlah bahan resep harus lebih dari nol.',
        ]);
        $recipe = collect($validated['ingredients'] ?? [])
            ->filter(fn (mixed $quantity): bool => $quantity !== null && $quantity !== '')
            ->mapWithKeys(fn (mixed $quantity, string|int $ingredientId): array => [
                (int) $ingredientId => ['quantity' => $quantity],
            ]);

        if ($recipe->isNotEmpty()) {
            $activeIngredientCount = Ingredient::query()
                ->where('is_active', true)
                ->whereKey($recipe->keys())
                ->count();

            if ($activeIngredientCount !== $recipe->count()) {
                throw ValidationException::withMessages([
                    'ingredients' => 'Resep hanya dapat menggunakan bahan baku yang aktif.',
                ]);
            }
        }

        DB::transaction(function () use ($product, $recipe): void {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);
            $lockedProduct->ingredients()->sync($recipe->all());
        });

        return to_route('admin.inventory.index', ['product_id' => $product->id])
            ->with('success', 'Resep '.$product->name.' berhasil diperbarui.');
    }
}
