<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusLocation;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Ruta;

class BusTrackingController extends Controller
{
    /**
     * Actualizar ubicación GPS del bus (llamado por la app del chofer cada 10-30 segundos)
     * POST /api/driver/update-location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'accuracy' => 'nullable|numeric|min:0'
        ]);

        try {
            $driver = $request->user();

            // Verificar si el chofer tiene un viaje activo en este bus
            $activeTrip = Trip::where('bus_id', $request->bus_id)
                ->where('driver_id', $driver->id)
                ->whereNull('fin')
                ->first();

            // Crear registro de ubicación
            $location = BusLocation::create([
                'bus_id' => $request->bus_id,
                'driver_id' => $driver->id,
                'trip_id' => $activeTrip ? $activeTrip->id : null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'accuracy' => $request->accuracy,
                'is_active' => $activeTrip ? true : false,
                'recorded_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ubicación actualizada',
                'location' => $location
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar ubicación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener buses cercanos a una ubicación (para pasajeros)
     * GET /api/passenger/nearby-buses?latitude=-17.7833&longitude=-63.1823&radius=2&ruta_id=5
     */
    public function getNearbyBuses(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:50', // Máximo 50km
            'ruta_id' => 'nullable|integer|exists:rutas,id' // Filtrar por línea específica
        ]);

        $radiusKm = $request->get('radius', 20); // Default 20km (aumentado de 2km)
        $rutaId = $request->get('ruta_id');

        try {
            // Buscar buses cercanos
            $nearbyBuses = BusLocation::findNearby(
                $request->latitude,
                $request->longitude,
                $radiusKm,
                5 // Últimos 5 minutos
            );

            // Filtrar por línea si se especificó
            if ($rutaId) {
                $nearbyBuses = $nearbyBuses->filter(function ($location) use ($rutaId) {
                    return $location->bus && $location->bus->ruta_id == $rutaId;
                });
            }

            // Formatear respuesta
            $buses = $nearbyBuses->map(function ($location) {
                return [
                    'bus_id' => $location->bus_id,
                    'bus_plate' => $location->bus->plate ?? 'N/A',
                    'bus_number' => $location->bus->bus_number ?? 'N/A',
                    'ruta_id' => $location->bus->ruta_id ?? null,
                    'ruta_nombre' => $location->bus->ruta->nombre ?? 'N/A',
                    'ruta_descripcion' => $location->bus->ruta->descripcion ?? '',
                    'driver_name' => $location->driver->name ?? 'N/A',
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'speed' => $location->speed ? (float) $location->speed : null,
                    'heading' => $location->heading ? (float) $location->heading : null,
                    'distance_km' => $location->distance_km,
                    'distance_meters' => round($location->distance_km * 1000),
                    'last_update' => $location->recorded_at->diffForHumans(),
                    'last_update_timestamp' => $location->recorded_at->toIso8601String(),
                    'is_active' => $location->is_active
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $buses->count(),
                'radius_km' => $radiusKm,
                'user_location' => [
                    'latitude' => (float) $request->latitude,
                    'longitude' => (float) $request->longitude
                ],
                'buses' => $buses->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar buses cercanos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las líneas disponibles (para el selector del pasajero)
     * GET /api/passenger/available-routes
     */
    public function getAvailableRoutes()
    {
        try {
            $rutas = Ruta::select('id', 'nombre', 'descripcion')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'routes' => $rutas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener líneas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ubicación actual de un bus específico
     * GET /api/passenger/bus-location/{busId}
     */
    public function getBusLocation($busId)
    {
        try {
            $location = BusLocation::getLatestForBus($busId);

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ubicación disponible para este bus'
                ], 404);
            }

            // Verificar que la ubicación no sea muy antigua (más de 10 minutos)
            if ($location->recorded_at < now()->subMinutes(10)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La última ubicación del bus es muy antigua',
                    'last_seen' => $location->recorded_at->diffForHumans()
                ], 404);
            }

            return response()->json([
                'success' => true,
                'bus' => [
                    'bus_id' => $location->bus_id,
                    'bus_plate' => $location->bus->plate ?? 'N/A',
                    'ruta_nombre' => $location->bus->ruta->nombre ?? 'N/A',
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'speed' => $location->speed ? (float) $location->speed : null,
                    'heading' => $location->heading ? (float) $location->heading : null,
                    'last_update' => $location->recorded_at->diffForHumans(),
                    'is_active' => $location->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ubicación del bus',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
