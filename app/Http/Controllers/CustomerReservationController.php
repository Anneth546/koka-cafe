<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\DiningTable;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerReservationController extends Controller
{
    public function create(): View
    {
        $tables = DiningTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get(['id', 'number', 'label', 'area', 'capacity']);

        return view('customer.reservations.create', compact('tables'));
    }

    public function store(StoreReservationRequest $request, ReservationService $reservationService): RedirectResponse
    {
        $reservationService->create($request->validated());

        return to_route('customer.reservations.create')
            ->with('success', 'Reservasi berhasil dicatat dan sudah masuk ke admin KOKA CAFE.');
    }
}
