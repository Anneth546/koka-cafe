<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $searchTerm = trim($validated['q'] ?? '');
        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('paidOrders', 'total')
            ->withMax('orders', 'created_at')
            ->when($searchTerm !== '', fn ($query) => $query->where(function ($searchQuery) use ($searchTerm): void {
                $searchQuery
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('phone', 'like', '%'.$searchTerm.'%');
            }))
            ->orderByDesc('orders_max_created_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->each(function (Customer $customer): void {
            $customer->setAttribute(
                'last_order_at',
                $customer->orders_max_created_at
                    ? Carbon::parse($customer->orders_max_created_at)
                    : null
            );
        });

        return view('admin.customers.index', compact('customers', 'searchTerm'));
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'orders' => fn ($query) => $query
                ->with(['items', 'diningTable'])
                ->latest(),
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/', Rule::unique('customers', 'phone')->ignore($customer->id)],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon.',
            'phone.unique' => 'Nomor telepon sudah terdaftar untuk pelanggan lain.',
        ]);

        $customer->update($validated);

        return to_route('admin.customers.show', $customer)->with('success', 'Data pelanggan berhasil diperbarui.');
    }
}
