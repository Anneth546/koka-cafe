<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_suppliers_ingredients_and_record_stock_movements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.inventory.suppliers.store'), [
            'name' => 'Susu Segar Jaya',
            'phone' => '08123456789',
            'email' => 'supplier@example.test',
            'address' => 'Bandung',
        ])->assertRedirect(route('admin.inventory.index'));

        $supplier = Supplier::query()->firstOrFail();
        $this->post(route('admin.inventory.ingredients.store'), [
            'supplier_id' => $supplier->id,
            'name' => 'Susu',
            'unit' => 'ml',
            'low_stock_threshold' => 100,
            'unit_cost' => 20,
            'is_active' => 1,
        ])->assertRedirect(route('admin.inventory.index'));

        $ingredient = Ingredient::query()->firstOrFail();
        $this->post(route('admin.inventory.movements.store'), [
            'ingredient_id' => $ingredient->id,
            'type' => 'purchase',
            'quantity' => 500,
            'supplier_id' => $supplier->id,
            'unit_cost' => 25,
            'notes' => 'Pembelian pertama',
        ])->assertRedirect(route('admin.inventory.index'));

        $this->assertSame('500.000', $ingredient->fresh()->stock_on_hand);
        $this->assertSame('25.00', $ingredient->fresh()->unit_cost);
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'supplier_id' => $supplier->id,
            'changed_by' => $admin->id,
            'type' => 'purchase',
            'quantity' => '500.000',
        ]);

        $this->post(route('admin.inventory.movements.store'), [
            'ingredient_id' => $ingredient->id,
            'type' => 'waste',
            'quantity' => 20,
            'notes' => 'Kemasan rusak',
        ])->assertRedirect(route('admin.inventory.index'));

        $this->post(route('admin.inventory.movements.store'), [
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => 450,
        ])->assertRedirect(route('admin.inventory.index'));

        $this->assertSame('450.000', $ingredient->fresh()->stock_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => '-30.000',
        ]);
    }

    public function test_admin_can_assign_a_recipe_and_view_low_stock_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = $this->createIngredient([
            'name' => 'Kopi',
            'unit' => 'g',
            'stock_on_hand' => 5,
            'low_stock_threshold' => 10,
        ]);
        $product = Product::factory()->create(['name' => 'Koka Espresso']);

        $this->actingAs($admin)
            ->put(route('admin.inventory.recipes.update', $product), [
                'ingredients' => [$ingredient->id => '10.000'],
            ])
            ->assertRedirect(route('admin.inventory.index', ['product_id' => $product->id]));

        $this->assertDatabaseHas('ingredient_product', [
            'ingredient_id' => $ingredient->id,
            'product_id' => $product->id,
            'quantity' => '10.000',
        ]);

        $this->get(route('admin.inventory.index', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Koka Espresso')
            ->assertSee('Stok menipis')
            ->assertSee('Kopi');
    }

    public function test_admin_cannot_change_an_ingredient_unit_while_it_is_used_in_a_recipe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = $this->createIngredient(['unit' => 'g']);
        $product = Product::factory()->create();
        $product->ingredients()->attach($ingredient->id, ['quantity' => '10.000']);

        $this->actingAs($admin)
            ->from(route('admin.inventory.index'))
            ->patch(route('admin.inventory.ingredients.update', $ingredient), [
                'name' => $ingredient->name,
                'unit' => 'ml',
                'low_stock_threshold' => 0,
                'unit_cost' => 0,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHasErrors('unit');

        $this->assertSame('g', $ingredient->fresh()->unit);
    }

    public function test_paid_order_consumes_recipe_stock_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = $this->createIngredient([
            'name' => 'Kopi',
            'unit' => 'g',
            'stock_on_hand' => 100,
        ]);
        $product = Product::factory()->create(['name' => 'Koka Espresso']);
        $product->ingredients()->attach($ingredient->id, ['quantity' => '10.000']);
        $order = Order::factory()->create(['total' => 75000]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 25000,
            'quantity' => 3,
            'line_total' => 75000,
        ]);

        $this->actingAs($admin)
            ->patch(route('staff.orders.update-status', $order), [
                'status' => 'paid',
                'payment_method' => 'qris',
            ])
            ->assertRedirect(route('staff.orders.index'));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertSame('70.000', $ingredient->fresh()->stock_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'order_id' => $order->id,
            'changed_by' => $admin->id,
            'type' => 'usage',
            'quantity' => '-30.000',
        ]);

        $this->from(route('staff.orders.index'))
            ->patch(route('staff.orders.update-status', $order), [
                'status' => 'paid',
                'payment_method' => 'qris',
            ])
            ->assertRedirect(route('staff.orders.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame('70.000', $ingredient->fresh()->stock_on_hand);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_payment_is_rejected_when_recipe_stock_is_insufficient(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = $this->createIngredient([
            'name' => 'Susu',
            'unit' => 'ml',
            'stock_on_hand' => 15,
        ]);
        $product = Product::factory()->create();
        $product->ingredients()->attach($ingredient->id, ['quantity' => '10.000']);
        $order = Order::factory()->create();
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 32000,
            'quantity' => 2,
            'line_total' => 64000,
        ]);

        $this->actingAs($admin)
            ->from(route('staff.orders.index'))
            ->patch(route('staff.orders.update-status', $order), [
                'status' => 'paid',
                'payment_method' => 'qris',
            ])
            ->assertRedirect(route('staff.orders.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_payment']);
        $this->assertSame('15.000', $ingredient->fresh()->stock_on_hand);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_checkout_and_walk_in_orders_reject_quantities_exceeding_recipe_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = $this->createIngredient([
            'name' => 'Susu',
            'unit' => 'ml',
            'stock_on_hand' => 15,
        ]);
        $product = Product::factory()->create();
        $product->ingredients()->attach($ingredient->id, ['quantity' => '10.000']);
        $checkoutKey = (string) Str::uuid();
        $csrfToken = 'inventory-checkout-token';

        $this->withSession([
            '_token' => $csrfToken,
            'cart' => [$product->id => 2],
            'checkout_key' => $checkoutKey,
        ])->post(route('orders.store'), [
            '_token' => $csrfToken,
            'checkout_key' => $checkoutKey,
        ])->assertSessionHasErrors('items');

        $this->actingAs($admin)
            ->from(route('staff.orders.index'))
            ->post(route('staff.orders.manual.store'), [
                'items' => [$product->id => 2],
            ])
            ->assertRedirect(route('staff.orders.index'))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('15.000', $ingredient->fresh()->stock_on_hand);
    }

    public function test_customer_catalog_hides_products_when_a_recipe_ingredient_is_insufficient(): void
    {
        $product = Product::factory()->create(['name' => 'Latte Persediaan']);
        $ingredient = $this->createIngredient([
            'name' => 'Susu',
            'unit' => 'ml',
            'stock_on_hand' => 5,
        ]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => '10.000']);

        $this->get('/menu')
            ->assertOk()
            ->assertDontSee('Latte Persediaan');

        $ingredient->forceFill(['stock_on_hand' => '10.000'])->save();

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Latte Persediaan');
    }

    public function test_non_admin_cannot_manage_inventory(): void
    {
        $nonAdmin = User::factory()->create(['role' => 'barista']);

        $this->actingAs($nonAdmin)
            ->get(route('admin.inventory.index'))
            ->assertForbidden();

        $this->actingAs($nonAdmin)
            ->post(route('admin.inventory.ingredients.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('ingredients', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createIngredient(array $attributes = []): Ingredient
    {
        $stockOnHand = $attributes['stock_on_hand'] ?? 0;
        unset($attributes['stock_on_hand']);

        $ingredient = Ingredient::create([
            'name' => 'Bahan contoh',
            'unit' => 'g',
            'low_stock_threshold' => 0,
            'unit_cost' => 0,
            'is_active' => true,
            ...$attributes,
        ]);
        $ingredient->forceFill([
            'stock_on_hand' => number_format((float) $stockOnHand, 3, '.', ''),
        ])->save();

        return $ingredient->fresh();
    }
}
