<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_an_available_product_updates_the_customer_cart(): void
    {
        $category = Category::create([
            'name' => 'Kopi',
            'slug' => 'kopi',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Latte',
            'slug' => 'latte',
            'price' => 32000,
            'is_available' => true,
        ]);

        $this->withSession(['_token' => 'cart-test-token'])
            ->post('/keranjang/items', [
                '_token' => 'cart-test-token',
                'product_id' => $product->id,
            ])
            ->assertRedirect('/keranjang')
            ->assertSessionHas('cart', [$product->id => 1]);
    }
}
