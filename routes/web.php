<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiningTableController as AdminDiningTableController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\InventoryRecipeController as AdminInventoryRecipeController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerHomeController;
use App\Http\Controllers\CustomerReservationController;
use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\DiningTableController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\WalkInOrderController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

Route::get('/', CustomerHomeController::class)->name('customer.home');
Route::get('/menu', CustomerHomeController::class)->name('customer.menu');
Route::get('/reservasi', [CustomerReservationController::class, 'create'])->name('customer.reservations.create');
Route::post('/reservasi', [CustomerReservationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('customer.reservations.store');
Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/keranjang/items/{product}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/keranjang/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::get('/meja/{token}', [DiningTableController::class, 'enter'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:30,1')
    ->name('dining-tables.enter');
Route::post('/pesanan', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('orders.store');
Route::get('/pesanan/{token}', [CheckoutController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('orders.show');
Route::post('/pesanan/{token}/ulasan', [CustomerReviewController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:5,1')
    ->name('orders.reviews.store');

Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('staff.login.store');
Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])
    ->middleware(EnsureStaffRole::class.':admin')
    ->name('staff.logout');

Route::middleware(EnsureStaffRole::class.':admin')
    ->prefix('/staff')
    ->name('staff.')
    ->group(function (): void {
        Route::get('/pesanan', [StaffOrderController::class, 'index'])->name('orders.index');
        Route::post('/pesanan/manual', [WalkInOrderController::class, 'store'])->name('orders.manual.store');
        Route::patch('/pesanan/{order}/cancel', [StaffOrderController::class, 'cancel'])->name('orders.cancel');
        Route::patch('/pesanan/{order}/status', [StaffOrderController::class, 'updateStatus'])
            ->name('orders.update-status');
    });

Route::middleware(EnsureStaffRole::class.':admin')
    ->prefix('/admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports/sales', [AdminDashboardController::class, 'salesReport'])->name('reports.sales');
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/tables', [AdminDiningTableController::class, 'index'])->name('tables.index');
        Route::post('/tables', [AdminDiningTableController::class, 'store'])->name('tables.store');
        Route::get('/tables/qr', [AdminDiningTableController::class, 'print'])->name('tables.print');
        Route::post('/tables/{diningTable}/qr/regenerate', [AdminDiningTableController::class, 'regenerateQr'])
            ->name('tables.qr.regenerate');
        Route::patch('/tables/{diningTable}', [AdminDiningTableController::class, 'update'])
            ->name('tables.update');
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [AdminReservationController::class, 'store'])->name('reservations.store');
        Route::patch('/reservations/{reservation}/status', [AdminReservationController::class, 'updateStatus'])
            ->name('reservations.update-status');
        Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/ingredients', [AdminInventoryController::class, 'storeIngredient'])->name('inventory.ingredients.store');
        Route::patch('/inventory/ingredients/{ingredient}', [AdminInventoryController::class, 'updateIngredient'])
            ->name('inventory.ingredients.update');
        Route::post('/inventory/suppliers', [AdminInventoryController::class, 'storeSupplier'])->name('inventory.suppliers.store');
        Route::put('/inventory/suppliers/{supplier}', [AdminInventoryController::class, 'updateSupplier'])
            ->name('inventory.suppliers.update');
        Route::post('/inventory/movements', [AdminInventoryController::class, 'storeMovement'])
            ->name('inventory.movements.store');
        Route::put('/inventory/products/{product}/recipe', [AdminInventoryRecipeController::class, 'update'])
            ->name('inventory.recipes.update');
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::put('/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::put('/reviews/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
        Route::get('/promotions', [AdminPromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [AdminPromotionController::class, 'store'])->name('promotions.store');
        Route::put('/promotions/{promotion}', [AdminPromotionController::class, 'update'])->name('promotions.update');
    });
