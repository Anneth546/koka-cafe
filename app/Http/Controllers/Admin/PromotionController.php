<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAttributes($request);
        $validated['code'] = Str::upper(trim($validated['code']));
        Promotion::create($validated);

        return to_route('admin.promotions.index')->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $this->validatedAttributes($request, $promotion);

        if ($validated['usage_limit'] !== null && (int) $validated['usage_limit'] < $promotion->usage_count) {
            throw ValidationException::withMessages([
                'usage_limit' => 'Kuota tidak boleh lebih kecil dari voucher yang sudah digunakan.',
            ]);
        }

        $validated['code'] = Str::upper(trim($validated['code']));
        $promotion->update($validated);

        return to_route('admin.promotions.index')->with('success', 'Voucher berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, ?Promotion $promotion = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code', ''))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promotions', 'code')->ignore($promotion?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'minimum_order' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'ends_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                Rule::when(filled($request->input('starts_at')), ['after:starts_at']),
            ],
            'is_active' => ['required', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'discount_type.in' => 'Pilih jenis potongan yang valid.',
            'ends_at.after' => 'Tanggal akhir harus sesudah tanggal mulai.',
        ]);

        if ($validated['discount_type'] === 'percentage' && (float) $validated['discount_value'] > 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Potongan persentase maksimal 100%.',
            ]);
        }

        $validated += [
            'maximum_discount' => null,
            'usage_limit' => null,
            'starts_at' => null,
            'ends_at' => null,
        ];

        return $validated;
    }
}
