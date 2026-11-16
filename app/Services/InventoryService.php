<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * @param  array<int, int>  $productQuantities
     */
    public function assertStockAvailable(array $productQuantities): void
    {
        $requirements = $this->ingredientRequirements($productQuantities, true);
        $ingredients = $this->lockIngredients($requirements);

        foreach ($requirements as $ingredientId => $requiredMilliUnits) {
            $ingredient = $ingredients->get($ingredientId);

            if (! $ingredient || ! $ingredient->is_active || self::toMilliUnits((string) $ingredient->stock_on_hand) < $requiredMilliUnits) {
                $name = $ingredient?->name ?? 'bahan resep';

                throw ValidationException::withMessages([
                    'items' => 'Stok '.$name.' tidak mencukupi untuk jumlah pesanan ini.',
                ]);
            }
        }
    }

    public function consumeOrder(Order $order, int $changedBy): void
    {
        $order->loadMissing('items');
        $productQuantities = [];

        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $productQuantities[$item->product_id] = ($productQuantities[$item->product_id] ?? 0) + $item->quantity;
        }
        $requirements = $this->ingredientRequirements($productQuantities, true);
        $ingredients = $this->lockIngredients($requirements);

        foreach ($requirements as $ingredientId => $requiredMilliUnits) {
            $ingredient = $ingredients->get($ingredientId);

            if (! $ingredient || ! $ingredient->is_active || self::toMilliUnits((string) $ingredient->stock_on_hand) < $requiredMilliUnits) {
                $name = $ingredient?->name ?? 'bahan resep';

                throw ValidationException::withMessages([
                    'status' => 'Stok '.$name.' tidak mencukupi. Pesanan belum dapat dibayar.',
                ]);
            }
        }

        foreach ($requirements as $ingredientId => $requiredMilliUnits) {
            $ingredient = $ingredients->get($ingredientId);
            $remainingMilliUnits = self::toMilliUnits((string) $ingredient->stock_on_hand) - $requiredMilliUnits;
            $ingredient->forceFill([
                'stock_on_hand' => self::formatMilliUnits($remainingMilliUnits),
            ])->save();
            $ingredient->inventoryMovements()->create([
                'order_id' => $order->id,
                'changed_by' => $changedBy,
                'type' => 'usage',
                'quantity' => self::formatMilliUnits(-$requiredMilliUnits),
                'unit_cost' => $ingredient->unit_cost,
                'notes' => 'Pemakaian untuk pesanan '.$order->order_number,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordManualMovement(array $data, int $changedBy): InventoryMovement
    {
        return DB::transaction(function () use ($data, $changedBy): InventoryMovement {
            $ingredient = Ingredient::query()
                ->lockForUpdate()
                ->findOrFail($data['ingredient_id']);
            $currentMilliUnits = self::toMilliUnits((string) $ingredient->stock_on_hand);
            $quantityMilliUnits = self::toMilliUnits((string) $data['quantity']);

            $changeMilliUnits = match ($data['type']) {
                'purchase' => $quantityMilliUnits,
                'waste' => -$quantityMilliUnits,
                'adjustment' => $quantityMilliUnits - $currentMilliUnits,
            };
            $newStockMilliUnits = $currentMilliUnits + $changeMilliUnits;

            if ($newStockMilliUnits < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok keluar melebihi stok bahan yang tersedia.',
                ]);
            }

            $attributes = [
                'stock_on_hand' => self::formatMilliUnits($newStockMilliUnits),
            ];
            $unitCost = $data['unit_cost'] ?? null;

            if ($data['type'] === 'purchase' && $unitCost !== null) {
                $attributes['unit_cost'] = $unitCost;
            }

            $ingredient->forceFill($attributes)->save();

            return $ingredient->inventoryMovements()->create([
                'supplier_id' => $data['type'] === 'purchase' ? ($data['supplier_id'] ?? null) : null,
                'changed_by' => $changedBy,
                'type' => $data['type'],
                'quantity' => self::formatMilliUnits($changeMilliUnits),
                'unit_cost' => $data['type'] === 'purchase' ? $unitCost : $ingredient->unit_cost,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<int, int>  $productQuantities
     * @return array<int, int>
     */
    private function ingredientRequirements(array $productQuantities, bool $lockProducts): array
    {
        if ($productQuantities === []) {
            return [];
        }

        $productIds = array_map('intval', array_keys($productQuantities));

        if ($lockProducts) {
            Product::query()
                ->whereKey($productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        $products = Product::query()
            ->with('ingredients')
            ->whereKey($productIds)
            ->get();
        $requirements = [];

        foreach ($products as $product) {
            $orderedQuantity = $productQuantities[$product->id] ?? 0;

            foreach ($product->ingredients as $ingredient) {
                $requiredMilliUnits = self::toMilliUnits((string) $ingredient->pivot->quantity) * $orderedQuantity;
                $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + $requiredMilliUnits;
            }
        }

        return $requirements;
    }

    /**
     * @param  array<int, int>  $requirements
     * @return Collection<int, Ingredient>
     */
    private function lockIngredients(array $requirements): Collection
    {
        if ($requirements === []) {
            return new Collection;
        }

        return Ingredient::query()
            ->whereKey(array_keys($requirements))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private static function toMilliUnits(string $quantity): int
    {
        return (int) round((float) $quantity * 1000);
    }

    private static function formatMilliUnits(int $quantity): string
    {
        $sign = $quantity < 0 ? '-' : '';
        $absoluteQuantity = abs($quantity);

        return $sign.intdiv($absoluteQuantity, 1000).'.'.str_pad((string) ($absoluteQuantity % 1000), 3, '0', STR_PAD_LEFT);
    }
}
