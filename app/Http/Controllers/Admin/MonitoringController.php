<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Trip;
use App\Models\Card;
use App\Models\Transaction;

class MonitoringController extends Controller
{
    public function trips(Request $request)
    {
        $drivers = User::where('role', 'driver')->get();
        $selectedDriverId = $request->input('driver_id');

        $trips = collect(); // Colección vacía por defecto

        if ($selectedDriverId) {
            $trips = Trip::with(['bus.ruta', 'card'])
                        ->where('driver_id', $selectedDriverId)
                        ->orderBy('fecha', 'desc')
                        ->orderBy('inicio', 'desc')
                        ->paginate(10);
        }

        return view('admin.monitoring.trips', compact('drivers', 'selectedDriverId', 'trips'));
    }

    public function cardTransactions(Request $request)
    {
        $cards = Card::all();
        $selectedCardId = $request->input('card_id');

        $transactions = collect(); // Colección vacía por defecto

        if ($selectedCardId) {
            $transactions = Transaction::with(['card.passenger', 'trip.bus.ruta'])
                                    ->where('card_id', $selectedCardId)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);
        }

        return view('admin.monitoring.card_transactions', compact('cards', 'selectedCardId', 'transactions'));
    }
}
