<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusLocation;
use App\Models\Ruta;
use App\Models\Trip;
use App\Models\Bus;

class RealtimeController extends Controller
{
    public function index()
    {
        // Obtener todas las líneas para el filtro
        $rutas = Ruta::select('id', 'nombre', 'descripcion')
            ->orderBy('nombre')
            ->get();

        return view('admin.realtime.index', compact('rutas'));
    }

    /**
     * API endpoint para obtener buses activos en tiempo real
     * GET /admin/realtime/active-buses?ruta_id=5
     */
    public function getActiveBuses(Request $request)
    {
        $rutaId = $request->query('ruta_id');

        // Obtener últimas ubicaciones de buses activos (últimos 5 minutos)
        $locationsQuery = BusLocation::with(['bus.ruta', 'driver', 'trip.transactions'])
            ->where('is_active', true)
            ->where('recorded_at', '>=', now()->subMinutes(5))
            ->whereHas('trip', function($q) {
                $q->whereNull('fin'); // Solo viajes activos
            });

        // Filtrar por línea si se especifica
        if ($rutaId) {
            $locationsQuery->whereHas('bus', function($q) use ($rutaId) {
                $q->where('ruta_id', $rutaId);
            });
        }

        // Obtener solo la última ubicación de cada bus
        $locations = $locationsQuery->get()
            ->groupBy('bus_id')
            ->map(function($busLocations) {
                return $busLocations->sortByDesc('recorded_at')->first();
            })
            ->values();

        // Formatear respuesta
        $buses = $locations->map(function($location) {
            $trip = $location->trip;
            // Calcular ganancias del viaje incluyendo fare, refund y refund_reversal
            $tripEarnings = $trip ? $trip->transactions()
                                        ->whereIn('type', ['fare', 'refund', 'refund_reversal'])
                                        ->sum('amount') : 0;

            return [
                'bus_id' => $location->bus_id,
                'bus_plate' => $location->bus->plate ?? 'N/A',
                'bus_number' => $location->bus->bus_number ?? 'N/A',
                'ruta_id' => $location->bus->ruta_id ?? null,
                'ruta_nombre' => $location->bus->ruta->nombre ?? 'N/A',
                'ruta_descripcion' => $location->bus->ruta->descripcion ?? '',
                'driver_name' => $location->driver->name ?? 'N/A',
                'driver_id' => $location->driver_id,
                'trip_id' => $location->trip_id,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'speed' => $location->speed ? (float) $location->speed : null,
                'heading' => $location->heading ? (float) $location->heading : null,
                'trip_earnings' => number_format($tripEarnings, 2),
                'trip_earnings_raw' => $tripEarnings,
                'trip_start' => $trip ? $trip->inicio : null,
                'trip_start_formatted' => $trip && $trip->inicio ? \Carbon\Carbon::parse($trip->inicio)->format('H:i') : null,
                'trip_end' => $trip ? $trip->fin : null,
                'trip_end_formatted' => $trip && $trip->fin ? \Carbon\Carbon::parse($trip->fin)->format('H:i') : 'En curso',
                'last_update' => $location->recorded_at->diffForHumans(),
                'last_update_timestamp' => $location->recorded_at->toIso8601String(),
                'is_active' => $location->is_active
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $buses->count(),
            'buses' => $buses->values()
        ]);
    }
}
