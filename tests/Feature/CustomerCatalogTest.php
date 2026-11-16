<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_available_products_from_the_database(): void
    {
        $category = Category::create([
            'name' => 'Kopi',
            'slug' => 'kopi',
            'sort_order' => 1,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Iced Koka Latte',
            'slug' => 'iced-koka-latte',
            'description' => 'Espresso dan susu segar.',
            'price' => 32000,
            'is_available' => true,
            'is_featured' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Menu Tidak Tersedia',
            'slug' => 'menu-tidak-tersedia',
            'price' => 25000,
            'is_available' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Iced Koka Latte')
            ->assertSee('Rp 32.000')
            ->assertDontSee('Menu Tidak Tersedia');
    }

    public function test_customer_can_filter_the_menu_by_category(): void
    {
        $coffee = Category::create(['name' => 'Kopi', 'slug' => 'kopi']);
        $tea = Category::create(['name' => 'Teh', 'slug' => 'teh']);

        Product::create([
            'category_id' => $coffee->id,
            'name' => 'Kopi Susu',
            'slug' => 'kopi-susu',
            'price' => 28000,
        ]);
        Product::create([
            'category_id' => $tea->id,
            'name' => 'Teh Melati',
            'slug' => 'teh-melati',
            'price' => 22000,
        ]);

        $this->get('/menu?kategori=teh')
            ->assertOk()
            ->assertSee('Teh Melati')
            ->assertDontSee('Kopi Susu');
    }

    public function test_customer_does_not_see_products_from_an_inactive_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Kategori Rahasia',
            'slug' => 'kategori-rahasia',
            'is_active' => false,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Produk Tersembunyi',
            'slug' => 'produk-tersembunyi',
            'is_available' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Produk Tersembunyi');
    }

    public function test_customer_can_search_products_by_name_or_description(): void
    {
        Product::factory()->create([
            'name' => 'Matcha Cloud',
            'slug' => 'matcha-cloud-search',
            'description' => 'Matcha lembut dengan susu.',
        ]);
        Product::factory()->create([
            'name' => 'Koka Americano',
            'slug' => 'koka-americano-search',
            'description' => 'Espresso dan air panas.',
        ]);

        $this->get('/menu?q=matcha')
            ->assertOk()
            ->assertSee('Matcha Cloud')
            ->assertDontSee('Koka Americano');
    }

    public function test_landing_page_links_staff_to_the_staff_login_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login staff')
            ->assertSee(route('staff.login'));
    }
}
