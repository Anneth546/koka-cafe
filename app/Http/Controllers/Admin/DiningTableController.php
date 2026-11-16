<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DiningTableController extends Controller
{
    private const TABLE_STATUSES = [
        'available' => 'Kosong',
        'occupied' => 'Terisi',
        'cleaning' => 'Perlu dibersihkan',
    ];

    public function index(): View
    {
        $tables = DiningTable::query()
            ->withCount('orders')
            ->withCount(['reservations' => fn ($query) => $query->whereIn('status', ['confirmed', 'seated'])])
            ->orderBy('number')
            ->get();
        $activeTableCount = $tables->where('is_active', true)->count();

        return view('admin.tables.index', [
            'activeTableCount' => $activeTableCount,
            'tableStatuses' => self::TABLE_STATUSES,
            'tables' => $tables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:24', 'unique:dining_tables,number'],
            'label' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:255'],
            'status' => ['required', Rule::in(array_keys(self::TABLE_STATUSES))],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'number.required' => 'Nomor meja wajib diisi.',
            'number.unique' => 'Nomor meja sudah digunakan.',
            'capacity.required' => 'Kapasitas meja wajib diisi.',
            'capacity.min' => 'Kapasitas meja minimal satu orang.',
            'status.in' => 'Status meja tidak valid.',
        ]);

        DiningTable::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return to_route('admin.tables.index')->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, DiningTable $diningTable): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['sometimes', 'required', 'string', 'max:24', Rule::unique('dining_tables', 'number')->ignore($diningTable->id)],
            'label' => ['sometimes', 'nullable', 'string', 'max:80'],
            'area' => ['sometimes', 'nullable', 'string', 'max:50'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(array_keys(self::TABLE_STATUSES))],
            'is_active' => ['required', 'boolean'],
        ], [
            'is_active.required' => 'Status meja wajib dipilih.',
            'is_active.boolean' => 'Status meja tidak valid.',
            'number.unique' => 'Nomor meja sudah digunakan.',
            'capacity.min' => 'Kapasitas meja minimal satu orang.',
            'status.in' => 'Status meja tidak valid.',
        ]);

        $updates = collect($validated)->except('is_active')->all();
        $updates['is_active'] = (bool) $validated['is_active'];
        $diningTable->update($updates);

        return to_route('admin.tables.index')->with('success', 'Perubahan meja berhasil disimpan.');
    }

    public function regenerateQr(DiningTable $diningTable): RedirectResponse
    {
        $diningTable->update(['qr_token' => Str::random(48)]);

        return to_route('admin.tables.index')->with('success', 'QR meja '.$diningTable->number.' berhasil dibuat ulang.');
    }

    public function print(Request $request): Response|RedirectResponse
    {
        $exitCode = Artisan::call('koka:qr-tables', [
            '--base-url' => (string) $request->query('base_url', $request->getSchemeAndHttpHost()),
        ]);

        if ($exitCode !== 0) {
            return to_route('admin.tables.index')->withErrors([
                'qr' => trim(Artisan::output()) ?: 'QR meja tidak dapat dibuat.',
            ]);
        }

        $html = Storage::disk('local')->get('qr-meja.html');

        if (! is_string($html)) {
            return to_route('admin.tables.index')->withErrors([
                'qr' => 'File QR tidak ditemukan setelah dibuat.',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
