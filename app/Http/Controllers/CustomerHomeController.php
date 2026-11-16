<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'kategori' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $categorySlug = $filters['kategori'] ?? null;
        $searchTerm = trim($filters['q'] ?? '');

        $products = Product::query()
            ->with('category')
            ->availableForSale()
            ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true))
            ->when($categorySlug, fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $categorySlug)
            ))
            ->when($searchTerm !== '', fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($searchTerm): void {
                $searchQuery
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            }))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn (Builder $query) => $query->availableForSale()])
            ->orderBy('sort_order')
            ->get();
        $reviews = Review::query()
            ->where('is_published', true)
            ->with(['customer', 'order'])
            ->latest()
            ->limit(6)
            ->get();
        $table = DiningTable::query()
            ->whereKey(session('dining_table_id'))
            ->where('is_active', true)
            ->first();

        return view('customer.home', compact('categories', 'products', 'categorySlug', 'searchTerm', 'table', 'reviews'));
    }
}
