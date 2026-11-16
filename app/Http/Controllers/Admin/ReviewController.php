<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()
            ->with(['customer', 'order'])
            ->latest()
            ->paginate(20);
        $pendingReviewCount = Review::query()->where('is_published', false)->count();
        $averageRating = Review::query()->where('is_published', true)->avg('rating');

        return view('admin.reviews.index', compact('averageRating', 'pendingReviewCount', 'reviews'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);
        $review->update($validated);

        return to_route('admin.reviews.index')->with(
            'success',
            $review->is_published ? 'Ulasan dipublikasikan.' : 'Ulasan disembunyikan.'
        );
    }
}
