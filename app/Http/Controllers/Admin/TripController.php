<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Bus;
use App\Models\Ruta;

class TripController extends Controller
{
    public function index(Request $request)
    {
        // Paginación manual
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));

        // Filtros
        $driverId = $request->query('driver_id');
        $busId = $request->query('bus_id');
        $date = $request->query('date');

        // Datos para filtros UI
        $drivers = \App\Models\User::where('role', 'driver')->where('active', 1)->get();
        $buses = Bus::all();

        // Query de trips con filtros
        $tripsQuery = Trip::with(['bus.ruta','ruta','driver'])->withSum('transactions', 'amount');

        if ($driverId) {
            $tripsQuery->where('driver_id', $driverId);
        }

        if ($busId) {
            $tripsQuery->where('bus_id', $busId);
        }

        if ($date) {
            $tripsQuery->whereDate('fecha', $date);
        }

        // Contar total y calcular paginación
        $totalTrips = $tripsQuery->count();
        $hasMore = $totalTrips > ($page * $perPage);
        $hasPrev = $page > 1;

        $trips = $tripsQuery->orderBy('fecha', 'desc')
            ->orderBy('inicio', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return view('admin.trips.index', compact('trips', 'drivers', 'buses', 'page', 'hasMore', 'hasPrev', 'totalTrips'));
    }

    public function create()
    {
        $buses = Bus::with('ruta')->get();
        $rutas = Ruta::all();
        $drivers = \App\Models\User::where('role', 'driver')->where('active', 1)->get();
        return view('admin.trips.create', compact('buses','rutas','drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'ruta_id' => 'required|exists:rutas,id',
            'driver_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after:inicio'
        ]);

        Trip::create([
            'fecha' => $request->fecha,
            'ruta_id' => $request->ruta_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'inicio' => $request->inicio,
            'fin' => $request->fin,
        ]);

        return redirect()->route('admin.trips.index')->with('success','Viaje registrado.');
    }

    public function edit(Trip $trip)
    {
        $buses = Bus::with('ruta')->get();
        $rutas = Ruta::all();
        $drivers = \App\Models\User::where('role', 'driver')->where('active', 1)->get();
        return view('admin.trips.edit', compact('trip', 'buses', 'rutas', 'drivers'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'ruta_id' => 'required|exists:rutas,id',
            'driver_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after:inicio'
        ]);

        $trip->update([
            'fecha' => $request->fecha,
            'ruta_id' => $request->ruta_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'inicio' => $request->inicio,
            'fin' => $request->fin,
        ]);

        return redirect()->route('admin.trips.index')->with('success','Viaje actualizado.');
    }

    public function destroy(Trip $trip)
    {
        $trip->delete();
        return redirect()->route('admin.trips.index')->with('success','Viaje eliminado.');
    }

    /**
     * API endpoint para obtener trips via AJAX
     */
    public function getTripsData(Request $request)
    {
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));
        $driverId = $request->query('driver_id');
        $busId = $request->query('bus_id');
        $date = $request->query('date');

        $tripsQuery = Trip::with(['bus.ruta','ruta','driver'])->withSum('transactions', 'amount');

        if ($driverId) {
            $tripsQuery->where('driver_id', $driverId);
        }

        if ($busId) {
            $tripsQuery->where('bus_id', $busId);
        }

        if ($date) {
            $tripsQuery->whereDate('fecha', $date);
        }

        $totalTrips = $tripsQuery->count();
        $hasMore = $totalTrips > ($page * $perPage);
        $hasPrev = $page > 1;

        $trips = $tripsQuery->orderBy('fecha', 'desc')
            ->orderBy('inicio', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $html = view('admin.partials.trips-index-rows', compact('trips'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $page,
            'hasMore' => $hasMore,
            'hasPrev' => $hasPrev,
            'total' => $totalTrips,
            'showing_from' => ($page - 1) * $perPage + 1,
            'showing_to' => min($page * $perPage, $totalTrips)
        ]);
    }
}
