<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dining_table_id' => ['required', 'integer', 'exists:dining_tables,id'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:24', 'regex:/^[0-9+\s().-]+$/'],
            'party_size' => ['required', 'integer', 'min:1', 'max:30'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:now'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'dining_table_id.required' => 'Pilih meja untuk reservasi.',
            'dining_table_id.exists' => 'Meja yang dipilih tidak tersedia.',
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'customer_phone.required' => 'Nomor HP pelanggan wajib diisi.',
            'customer_phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon.',
            'party_size.min' => 'Jumlah tamu minimal satu orang.',
            'starts_at.after_or_equal' => 'Waktu reservasi tidak boleh di masa lalu.',
            'ends_at.after' => 'Waktu selesai harus sesudah waktu mulai.',
        ];
    }
}
