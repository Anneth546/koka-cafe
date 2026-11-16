<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use Illuminate\Http\RedirectResponse;

class DiningTableController extends Controller
{
    public function enter(string $token): RedirectResponse
    {
        $table = DiningTable::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        session()->regenerate();
        session(['dining_table_id' => $table->id]);

        return to_route('customer.menu')->with('table_label', $table->label ?? 'Meja '.$table->number);
    }
}
