<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'kopi' => ['name' => 'Kopi', 'description' => 'Racikan espresso dan kopi pilihan.', 'sort_order' => 1],
            'non-kopi' => ['name' => 'Non-Kopi', 'description' => 'Pilihan hangat dan segar tanpa kopi.', 'sort_order' => 2],
            'teh' => ['name' => 'Teh', 'description' => 'Teh klasik dan racikan buah yang menyegarkan.', 'sort_order' => 3],
            'makanan' => ['name' => 'Makanan Berat', 'description' => 'Sajian hangat untuk makan di tempat.', 'sort_order' => 4],
            'pastry' => ['name' => 'Pastry', 'description' => 'Pastry dan roti untuk teman kopi.', 'sort_order' => 5],
            'snack' => ['name' => 'Snack', 'description' => 'Camilan untuk dinikmati bersama.', 'sort_order' => 6],
        ];

        foreach ($categories as $slug => $attributes) {
            Category::updateOrCreate(['slug' => $slug], $attributes + ['is_active' => true]);
        }

        $products = [
            [
                'category' => 'kopi',
                'name' => 'Iced Koka Latte',
                'slug' => 'iced-koka-latte',
                'description' => 'Espresso seimbang, susu segar, dan sentuhan manis yang lembut.',
                'price' => 32000,
                'image_url' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'kopi',
                'name' => 'Classic Cappuccino',
                'slug' => 'classic-cappuccino',
                'description' => 'Espresso dengan lapisan susu berbusa yang halus.',
                'price' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'kopi',
                'name' => 'Espresso Tonic',
                'slug' => 'espresso-tonic',
                'description' => 'Espresso dengan tonic yang ringan dan menyegarkan.',
                'price' => 34000,
                'image_url' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'kopi',
                'name' => 'Koka Americano',
                'slug' => 'koka-americano',
                'description' => 'Espresso pilihan dengan air panas, bersih dan seimbang.',
                'price' => 26000,
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'kopi',
                'name' => 'Caramel Macchiato',
                'slug' => 'caramel-macchiato',
                'description' => 'Susu lembut, espresso, dan karamel buatan barista.',
                'price' => 36000,
                'image_url' => 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'non-kopi',
                'name' => 'Matcha Cloud',
                'slug' => 'matcha-cloud',
                'description' => 'Matcha lembut dengan susu dingin dan foam ringan.',
                'price' => 35000,
                'image_url' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'non-kopi',
                'name' => 'Dark Chocolate',
                'slug' => 'dark-chocolate',
                'description' => 'Cokelat pekat dengan susu hangat atau dingin.',
                'price' => 32000,
                'image_url' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'non-kopi',
                'name' => 'Red Velvet Latte',
                'slug' => 'red-velvet-latte',
                'description' => 'Minuman red velvet creamy dengan susu segar.',
                'price' => 34000,
                'image_url' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'teh',
                'name' => 'Thai Tea',
                'slug' => 'thai-tea',
                'description' => 'Teh Thailand pekat dengan susu yang creamy.',
                'price' => 29000,
                'image_url' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'teh',
                'name' => 'Honey Lemon Tea',
                'slug' => 'honey-lemon-tea',
                'description' => 'Teh hitam, madu, dan lemon segar.',
                'price' => 27000,
                'image_url' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'teh',
                'name' => 'Lychee Tea',
                'slug' => 'lychee-tea',
                'description' => 'Teh melati dingin dengan manis buah leci.',
                'price' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'makanan',
                'name' => 'Chicken Katsu Rice',
                'slug' => 'chicken-katsu-rice',
                'description' => 'Ayam katsu renyah dengan nasi hangat dan saus gurih.',
                'price' => 48000,
                'image_url' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'makanan',
                'name' => 'Nasi Goreng KOKA',
                'slug' => 'nasi-goreng-koka',
                'description' => 'Nasi goreng racikan rumah dengan telur dan kerupuk.',
                'price' => 42000,
                'image_url' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'makanan',
                'name' => 'Grilled Chicken Sandwich',
                'slug' => 'grilled-chicken-sandwich',
                'description' => 'Roti panggang isi ayam, sayuran, dan saus khas KOKA.',
                'price' => 39000,
                'image_url' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'pastry',
                'name' => 'Butter Croissant',
                'slug' => 'butter-croissant',
                'description' => 'Pastry berlapis dengan aroma mentega yang hangat.',
                'price' => 26000,
                'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'pastry',
                'name' => 'Cinnamon Roll',
                'slug' => 'cinnamon-roll',
                'description' => 'Roti gulung kayu manis dengan glaze tipis.',
                'price' => 28000,
                'image_url' => 'https://images.unsplash.com/photo-1509365465985-25d11c17e812?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'pastry',
                'name' => 'Chocolate Muffin',
                'slug' => 'chocolate-muffin',
                'description' => 'Muffin cokelat lembut dengan potongan dark chocolate.',
                'price' => 24000,
                'image_url' => 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'snack',
                'name' => 'Koka French Fries',
                'slug' => 'koka-french-fries',
                'description' => 'Kentang goreng renyah dengan saus cocol pilihan.',
                'price' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=82',
                'is_featured' => true,
            ],
            [
                'category' => 'snack',
                'name' => 'Chicken Wings',
                'slug' => 'chicken-wings',
                'description' => 'Sayap ayam berbumbu, disajikan hangat dan gurih.',
                'price' => 36000,
                'image_url' => 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'snack',
                'name' => 'Pisang Goreng Madu',
                'slug' => 'pisang-goreng-madu',
                'description' => 'Pisang goreng renyah dengan madu dan wijen.',
                'price' => 22000,
                'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
            [
                'category' => 'snack',
                'name' => 'Cireng Bumbu Rujak',
                'slug' => 'cireng-bumbu-rujak',
                'description' => 'Cireng hangat dengan bumbu rujak manis pedas.',
                'price' => 20000,
                'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=900&q=82',
                'is_featured' => false,
            ],
        ];

        foreach ($products as $product) {
            $category = Category::where('slug', $product['category'])->firstOrFail();
            unset($product['category']);

            Product::updateOrCreate(
                ['slug' => $product['slug']],
                $product + ['category_id' => $category->id, 'is_available' => true]
            );
        }
    }
}
