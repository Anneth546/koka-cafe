<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerReviewController extends Controller
{
    public function store(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Pilih nilai ulasan dari satu sampai lima.',
            'rating.between' => 'Nilai ulasan harus antara satu dan lima.',
            'comment.max' => 'Ulasan maksimal 2.000 karakter.',
        ]);

        DB::transaction(function () use ($token, $validated): void {
            $order = Order::query()
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'completed') {
                throw ValidationException::withMessages([
                    'rating' => 'Ulasan hanya dapat dikirim setelah pesanan selesai.',
                ]);
            }

            if ($order->review()->exists()) {
                throw ValidationException::withMessages([
                    'rating' => 'Ulasan untuk pesanan ini sudah dikirim.',
                ]);
            }

            Review::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);
        });

        return to_route('orders.show', $token)->with('success', 'Terima kasih. Ulasan Anda menunggu moderasi admin.');
    }
}
