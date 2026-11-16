<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_review_a_completed_order_once_and_admin_can_publish_it(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'customer_name' => 'Nadia',
        ]);
        $reviewUrl = route('orders.reviews.store', $order->public_token);

        $this->post($reviewUrl, [
            'rating' => 5,
            'comment' => 'Kopinya enak.',
        ])->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);

        $order->update(['status' => 'completed']);

        $this->get(route('orders.show', $order->public_token))
            ->assertOk()
            ->assertSee('Bagaimana pengalamanmu?');

        $this->from(route('orders.show', $order->public_token))
            ->post($reviewUrl, [
                'rating' => 6,
                'comment' => 'Nilai di luar rentang.',
            ])
            ->assertRedirect(route('orders.show', $order->public_token))
            ->assertSessionHasErrors('rating');

        $this->post($reviewUrl, [
            'rating' => 5,
            'comment' => 'Kopinya enak.',
        ])
            ->assertRedirect(route('orders.show', $order->public_token))
            ->assertSessionHas('success');

        $review = Review::query()->firstOrFail();
        $this->assertFalse($review->is_published);
        $this->assertSame($order->id, $review->order_id);

        $this->from(route('orders.show', $order->public_token))
            ->post($reviewUrl, [
                'rating' => 4,
                'comment' => 'Ulasan duplikat.',
            ])
            ->assertRedirect(route('orders.show', $order->public_token))
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 1);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Kopinya enak.')
            ->assertSee('Menunggu moderasi');

        $this->put(route('admin.reviews.update', $review), ['is_published' => 1])
            ->assertRedirect(route('admin.reviews.index'));

        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee('Kopinya enak.')
            ->assertSee('Nadia');

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'is_published' => true]);
    }

    public function test_non_admin_cannot_moderate_customer_reviews(): void
    {
        $review = Review::query()->create([
            'order_id' => Order::factory()->create(['status' => 'completed'])->id,
            'rating' => 4,
            'comment' => 'Ulasan.',
        ]);
        $nonAdmin = User::factory()->create(['role' => 'barista']);

        $this->actingAs($nonAdmin)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        $this->actingAs($nonAdmin)
            ->put(route('admin.reviews.update', $review), ['is_published' => 1])
            ->assertForbidden();

        $this->assertFalse($review->fresh()->is_published);
    }
}
