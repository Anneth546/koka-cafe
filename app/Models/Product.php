<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'image_url',
        'is_available',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query
            ->where('is_available', true)
            ->whereDoesntHave('ingredients', function (Builder $ingredients): void {
                $ingredients->where(function (Builder $stockQuery): void {
                    $stockQuery
                        ->where('ingredients.is_active', false)
                        ->orWhereColumn('ingredients.stock_on_hand', '<', 'ingredient_product.quantity');
                });
            });
    }

    public function hasStockForRecipe(): bool
    {
        return $this->ingredients->every(fn (Ingredient $ingredient): bool => $ingredient->is_active
            && (float) $ingredient->stock_on_hand >= (float) $ingredient->pivot->quantity);
    }
}
