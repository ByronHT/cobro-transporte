<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\User;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $drivers = User::where('role', 'driver')->orderBy('name')->get();
        
        $driverId = $request->input('driver_id');
        $date = $request->input('date');

        $turnosQuery = Turno::with(['driver', 'busInicial', 'trips.ruta', 'trips.bus'])->latest();

        if ($driverId) {
            $turnosQuery->where('driver_id', $driverId);
        }

        if ($date) {
            $turnosQuery->whereDate('fecha', $date);
        }

        $turnos = $turnosQuery->paginate(20);

        return view('admin.turnos.index', compact('turnos', 'drivers', 'driverId', 'date'));
    }
}
