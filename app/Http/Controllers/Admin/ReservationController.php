<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\DiningTable;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    private const STATUS_LABELS = [
        'confirmed' => 'Dikonfirmasi',
        'seated' => 'Sudah datang',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public function index(): View
    {
        $reservations = Reservation::query()
            ->with('diningTable')
            ->latest('starts_at')
            ->paginate(20);
        $tables = DiningTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'statusLabels' => self::STATUS_LABELS,
            'tables' => $tables,
        ]);
    }

    public function store(StoreReservationRequest $request, ReservationService $reservationService): RedirectResponse
    {
        $reservationService->create($request->validated());

        return to_route('admin.reservations.index')->with('success', 'Reservasi berhasil ditambahkan.');
    }

    public function updateStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['seated', 'completed', 'cancelled'])],
        ], [
            'status.required' => 'Pilih status reservasi.',
            'status.in' => 'Status reservasi tidak valid.',
        ]);

        DB::transaction(function () use ($reservation, $validated): void {
            $lockedReservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id);
            $transitions = [
                'confirmed' => ['seated', 'cancelled'],
                'seated' => ['completed', 'cancelled'],
            ];

            if (! in_array($validated['status'], $transitions[$lockedReservation->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status reservasi sudah berubah atau tindakan tidak sesuai alur.',
                ]);
            }

            $lockedReservation->update(['status' => $validated['status']]);
        });

        return to_route('admin.reservations.index')->with('success', 'Status reservasi berhasil diperbarui.');
    }
}
