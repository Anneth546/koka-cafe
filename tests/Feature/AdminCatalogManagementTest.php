<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_an_uploaded_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Kopi']);

        $this->actingAs($admin)
            ->post('/admin/products', [
                'category_id' => $category->id,
                'name' => 'Koka Latte',
                'description' => 'Espresso dan susu segar.',
                'price' => 32000,
                'image_file' => UploadedFile::fake()->image('koka-latte.jpg'),
                'is_available' => 1,
                'is_featured' => 1,
            ])
            ->assertRedirect('/admin/products');

        $product = Product::query()->where('slug', 'koka-latte')->firstOrFail();
        $storedPath = substr($product->image_url, strlen('/storage/'));

        Storage::disk('public')->assertExists($storedPath);
        $this->assertTrue($product->is_available);
        $this->assertTrue($product->is_featured);
        $this->assertSame($category->id, $product->category_id);
    }

    public function test_admin_can_view_product_and_category_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Kopi']);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Koka Latte',
            'slug' => 'koka-latte-view',
        ]);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Koka Latte')
            ->assertSee('Tambah produk');

        $this->actingAs($admin)
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('Kopi')
            ->assertSee('Tambah kategori');
    }

    public function test_admin_can_update_and_delete_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Koka Latte',
            'slug' => 'koka-latte',
            'is_available' => true,
            'is_featured' => true,
        ]);

        $this->actingAs($admin)
            ->put('/admin/products/'.$product->id, [
                'category_id' => $category->id,
                'name' => 'Iced Koka Latte',
                'description' => 'Versi dingin.',
                'price' => 35000,
                'is_available' => 0,
                'is_featured' => 0,
            ])
            ->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Iced Koka Latte',
            'slug' => 'iced-koka-latte',
            'is_available' => false,
            'is_featured' => false,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/products/'.$product->id)
            ->assertRedirect('/admin/products');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_can_create_categories_but_cannot_delete_a_category_with_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/categories', [
                'name' => 'Minuman',
                'description' => 'Minuman kopi dan non-kopi.',
                'sort_order' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect('/admin/categories');

        $category = Category::query()->where('slug', 'minuman')->firstOrFail();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete('/admin/categories/'.$category->id)
            ->assertRedirect()
            ->assertSessionHasErrors('category');

        $this->assertModelExists($category);
    }

    public function test_non_admin_cannot_manage_catalog(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->get('/admin/products')
            ->assertForbidden();

        $this->actingAs($owner)
            ->get('/admin/categories')
            ->assertForbidden();
    }
}
