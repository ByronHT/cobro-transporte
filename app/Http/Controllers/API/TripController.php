<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Transaction;
use App\Models\PaymentEvent;
use App\Models\Turno;
use App\Models\TripWaypoint;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function userTrips(Request $request)
    {
        $user = $request->user();
        $per = intval($request->get('per', 20));

        $cardIds = $user->cards()->pluck('id')->toArray();

        $transactions = Transaction::with(['trip.ruta', 'trip.bus', 'trip.driver'])
            ->whereIn('card_id', $cardIds)
            ->where('type', 'fare')
            ->whereNotNull('trip_id')
            ->orderBy('created_at', 'desc')
            ->paginate($per);

        $transactions->getCollection()->transform(function ($transaction) {
            $trip = $transaction->trip;

            return (object) [
                'id' => $trip->id,
                'transaction_id' => $transaction->id,
                'fare' => $transaction->amount,
                'inicio' => $transaction->created_at,
                'fecha' => $transaction->created_at, // Alternativa por si el frontend usa 'fecha'
                'fin' => $trip->fin,
                'driver_id' => $trip->driver_id,
                'driver_name' => $trip->driver ? $trip->driver->name : 'Desconocido',
                'bus_plate' => $trip->bus ? $trip->bus->plate : 'N/A',
                'ruta' => $trip->ruta ? [
                    'id' => $trip->ruta->id,
                    'nombre' => $trip->ruta->nombre,
                    'descripcion' => $trip->ruta->descripcion,
                ] : null,
                'bus' => $trip->bus ? [
                    'id' => $trip->bus->id,
                    'plate' => $trip->bus->plate,
                ] : null,
                'driver' => $trip->driver ? [
                    'id' => $trip->driver->id,
                    'name' => $trip->driver->name,
                ] : null,
            ];
        });

        return response()->json($transactions);
    }

    public function start(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'driver_id' => 'required|integer|exists:users,id',
        ]);

        $activeTripForBus = Trip::where('bus_id', $request->bus_id)->whereNull('fin')->first();
        if ($activeTripForBus) {
            return response()->json(['message' => 'Este bus ya tiene un viaje activo.'], 409);
        }

        $activeTripForDriver = Trip::where('driver_id', $request->driver_id)->whereNull('fin')->first();
        if ($activeTripForDriver) {
            return response()->json(['message' => 'Este chofer ya tiene un viaje activo.'], 409);
        }

        $bus = Bus::find($request->bus_id);

        $trip = Trip::create([
            'fecha' => now(),
            'ruta_id' => $bus->ruta_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'inicio' => now(),
        ]);

        return response()->json($trip, 201);
    }

    public function end(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'reporte' => 'nullable|string|max:2000', // Reporte opcional del chofer
        ]);

        $trip = Trip::where('id', $request->trip_id)->whereNull('fin')->first();

        if (!$trip) {
            return response()->json(['message' => 'No se encontró un viaje activo con este ID.'], 404);
        }

        $trip->fin = now();

        if ($request->has('reporte') && !empty($request->reporte)) {
            $trip->reporte = $request->reporte;
        }

        $trip->save();

        return response()->json($trip);
    }

    /**
     * Finalizar viaje por bus_id (usado por dispositivo ESP8266 o app del chofer)
     */
    public function endByBus(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'reporte' => 'nullable|string|max:2000', // Reporte opcional del chofer
        ]);

        $trip = Trip::where('bus_id', $request->bus_id)->whereNull('fin')->first();

        if (!$trip) {
            return response()->json(['message' => 'No se encontró un viaje activo para este bus.'], 404);
        }

        $trip->fin = now();

        if ($request->has('reporte') && !empty($request->reporte)) {
            $trip->reporte = $request->reporte;
        }

        $trip->save();

        return response()->json([
            'message' => 'Viaje finalizado exitosamente.',
            'trip' => $trip
        ]);
    }

    /**
     * Retorna el estado del viaje activo del chofer autenticado.
     */
    public function currentTripStatus(Request $request)
    {
        $driver = $request->user();
        $activeTrip = Trip::with(['bus', 'ruta'])
                            ->where('driver_id', $driver->id)
                            ->whereNull('fin')
                            ->first();

        if (!$activeTrip) {
            return response()->json(['message' => 'No hay viaje activo.'], 404);
        }

        $tripEarnings = Transaction::where('trip_id', $activeTrip->id)
                                   ->whereIn('type', ['fare', 'refund', 'refund_reversal'])
                                   ->sum('amount');

        return response()->json([
            'user' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'balance' => $driver->balance,
            ],
            'trip' => [
                'id' => $activeTrip->id,
                'fecha' => $activeTrip->fecha,
                'inicio' => $activeTrip->inicio,
                'fin' => $activeTrip->fin,
                'bus_id' => $activeTrip->bus->id,
                'driver_id' => $driver->id,
                'bus' => [
                    'id' => $activeTrip->bus->id,
                    'plate' => $activeTrip->bus->plate,
                    'code' => $activeTrip->bus->code,
                ],
                'ruta' => [
                    'id' => $activeTrip->ruta->id,
                    'nombre' => $activeTrip->ruta->nombre,
                    'descripcion' => $activeTrip->ruta->descripcion,
                    'tarifa_base' => $activeTrip->ruta->tarifa_base,
                ],
                'fare' => $activeTrip->ruta->tarifa_base,
            ],
            'driver_balance' => number_format($driver->balance, 2),
            'trip_earnings' => number_format($tripEarnings, 2),
            'trip_earnings_raw' => $tripEarnings
        ]);
    }

    /**
     * Retorna las transacciones del viaje activo del chofer autenticado.
     */
    public function currentTripTransactions(Request $request)
    {
        $driver = $request->user();
        $activeTrip = Trip::where('driver_id', $driver->id)->whereNull('fin')->first();

        if (!$activeTrip) {
            return response()->json(['message' => 'No hay viaje activo.'], 404);
        }

        $transactions = Transaction::with(['card.user'])
                                    ->where('trip_id', $activeTrip->id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        $transactions->transform(function ($transaction) {
            $transaction->passenger_name = $transaction->card && $transaction->card->user
                ? $transaction->card->user->name
                : 'Desconocido';
            return $transaction;
        });

        return response()->json($transactions);
    }

    /**
     * Retorna los eventos de pago del viaje activo del chofer (últimos eventos desde el polling anterior)
     */
    public function currentTripPaymentEvents(Request $request)
    {
        $driver = $request->user();
        $activeTrip = Trip::where('driver_id', $driver->id)->whereNull('fin')->first();

        if (!$activeTrip) {
            return response()->json(['message' => 'No hay viaje activo.'], 404);
        }

        $lastEventId = $request->query('last_event_id', 0);

        $events = PaymentEvent::with(['passenger', 'card'])
            ->forTrip($activeTrip->id)
            ->where('id', '>', $lastEventId)
            ->recent()
            ->get();

        $events->transform(function ($event) {
            $event->passenger_name = $event->passenger ? $event->passenger->name : 'Desconocido';
            return $event;
        });

        return response()->json($events);
    }

    /**
     * Retorna los eventos de pago para un pasajero específico (sus propios intentos de pago)
     */
    public function passengerPaymentEvents(Request $request)
    {
        $user = $request->user();

        $cardIds = $user->cards()->pluck('id')->toArray();

        $lastEventId = $request->query('last_event_id', 0);

        $events = PaymentEvent::with(['trip.bus.ruta', 'trip.driver'])
            ->whereIn('card_id', $cardIds)
            ->where('id', '>', $lastEventId)
            ->recent()
            ->limit(50)
            ->get();

        return response()->json($events);
    }

    /**
     * Actualizar el reporte de un viaje activo sin finalizarlo
     * Permite al chofer registrar incidentes durante el viaje
     */
    public function updateTripReport(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'reporte' => 'required|string|max:2000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        $trip = Trip::where('id', $request->trip_id)->whereNull('fin')->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un viaje activo con este ID.'
            ], 404);
        }

        $driver = $request->user();
        if ($trip->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para modificar este viaje.'
            ], 403);
        }

        if ($request->hasFile('photo')) {
            if ($trip->photo_path && \Storage::disk('public')->exists($trip->photo_path)) {
                \Storage::disk('public')->delete($trip->photo_path);
            }

            $photoPath = $request->file('photo')->store('trip_reports', 'public');
            $trip->photo_path = $photoPath;
        }

        $timestamp = now()->format('d/m/Y H:i');
        $newReport = "\n[INCIDENTE REGISTRADO - {$timestamp}]\n";
        $newReport .= $request->reporte . "\n";

        if ($trip->reporte === 'Viaje concluido sin novedades') {
            $trip->reporte = "[INCIDENTE REGISTRADO - {$timestamp}]\n" . $request->reporte;
        } else {
            $trip->reporte .= $newReport;
        }

        $trip->save();

        return response()->json([
            'success' => true,
            'message' => 'Reporte registrado exitosamente',
            'trip' => $trip
        ]);
    }

    /**
     * Iniciar viaje con soporte para turno, ida/vuelta y cambio de bus
     */
    public function startWithTurno(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'tipo_viaje' => 'required|in:ida,vuelta',
            'cambio_bus' => 'nullable|boolean',
            'nuevo_bus_id' => 'required_if:cambio_bus,true|exists:buses,id'
        ]);

        $driver = $request->user();

        $turno = Turno::where('driver_id', $driver->id)
                      ->where('status', 'activo')
                      ->first();

        if (!$turno) {
            return response()->json([
                'error' => 'Debes iniciar un turno antes de comenzar un viaje'
            ], 400);
        }

        $activeTripForDriver = Trip::where('driver_id', $driver->id)->whereNull('fin')->first();
        if ($activeTripForDriver) {
            return response()->json([
                'error' => 'Ya tienes un viaje activo. Finalízalo antes de comenzar uno nuevo.'
            ], 409);
        }

        $busId = $request->bus_id;

        $activeTripForBus = Trip::where('bus_id', $busId)->whereNull('fin')->first();
        if ($activeTripForBus) {
            return response()->json([
                'error' => 'Este bus ya tiene un viaje activo.'
            ], 409);
        }

        $bus = Bus::find($busId);

        $trip = Trip::create([
            'fecha' => now()->toDate(),
            'ruta_id' => $bus->ruta_id,
            'bus_id' => $busId,
            'driver_id' => $driver->id,
            'turno_id' => $turno->id,
            'tipo_viaje' => $request->tipo_viaje,
            'inicio' => now(),
            'hora_salida_real' => now(),
            'cambio_bus' => $request->cambio_bus ?? false,
            'nuevo_bus_id' => $request->nuevo_bus_id ?? null,
            'status' => 'en_curso'
        ]);

        return response()->json([
            'message' => 'Viaje iniciado exitosamente',
            'trip' => $trip->load('bus', 'ruta', 'turno')
        ], 201);
    }

    /**
     * Finalizar viaje con soporte ida/vuelta
     */
    public function finishTrip(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'reporte' => 'nullable|string|max:2000',
            'finalizado_en_parada' => 'nullable|boolean',
            'crear_viaje_vuelta' => 'nullable|boolean' // Para viajes de ida
        ]);

        $driver = $request->user();

        $trip = Trip::where('id', $request->trip_id)
                    ->where('driver_id', $driver->id)
                    ->whereNull('fin')
                    ->first();

        if (!$trip) {
            return response()->json([
                'error' => 'No se encontró un viaje activo con este ID.'
            ], 404);
        }

        $waypoints = TripWaypoint::where('trip_id', $trip->id)
                                  ->orderBy('recorded_at')
                                  ->get()
                                  ->map(function($wp) {
                                      return [
                                          'lat' => (float)$wp->latitude,
                                          'lng' => (float)$wp->longitude,
                                          'time' => $wp->recorded_at->toIso8601String(),
                                          'speed' => $wp->speed ? (float)$wp->speed : null
                                      ];
                                  })
                                  ->toArray();

        $totalRecaudado = Transaction::where('trip_id', $trip->id)
                                     ->where('type', 'fare')
                                     ->sum('amount');

        $trip->fin = now();
        $trip->hora_llegada_real = now();
        $trip->total_recaudado = $totalRecaudado;
        $trip->recorrido_gps = $waypoints;
        $trip->finalizado_en_parada = $request->finalizado_en_parada ?? true;
        $trip->status = 'finalizado';

        if ($request->has('reporte') && !empty($request->reporte)) {
            $trip->reporte = $request->reporte;
        }

        $trip->save();

        TripWaypoint::where('trip_id', $trip->id)->delete();

        $viajeVuelta = null;
        if ($trip->tipo_viaje === 'ida' && $request->crear_viaje_vuelta) {
            $busParaVuelta = $trip->cambio_bus ? $trip->nuevo_bus_id : $trip->bus_id;

            $viajeVuelta = Trip::create([
                'fecha' => now()->toDate(),
                'ruta_id' => $trip->ruta_id,
                'bus_id' => $busParaVuelta,
                'driver_id' => $driver->id,
                'turno_id' => $trip->turno_id,
                'tipo_viaje' => 'vuelta',
                'inicio' => now(),
                'hora_salida_real' => now(),
                'status' => 'en_curso'
            ]);
        }

        return response()->json([
            'message' => 'Viaje finalizado exitosamente',
            'trip' => $trip->load('bus', 'ruta'),
            'viaje_vuelta' => $viajeVuelta ? $viajeVuelta->load('bus', 'ruta') : null
        ]);
    }

    /**
     * Guardar waypoint GPS del viaje actual
     */
    public function saveWaypoint(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0'
        ]);

        $driver = $request->user();

        $trip = Trip::where('id', $request->trip_id)
                    ->where('driver_id', $driver->id)
                    ->whereNull('fin')
                    ->first();

        if (!$trip) {
            return response()->json([
                'error' => 'Viaje no encontrado o no activo'
            ], 404);
        }

        $waypoint = TripWaypoint::create([
            'trip_id' => $request->trip_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed,
            'recorded_at' => now()
        ]);

        return response()->json([
            'message' => 'Ubicación guardada',
            'waypoint' => $waypoint
        ]);
    }

    /**
     * Obtener buses activos con filtro de tipo de viaje
     */
    public function getActiveBusesWithType(Request $request)
    {
        $tipoViaje = $request->query('tipo_viaje'); // 'ida' o 'vuelta'

        $query = Trip::with(['bus', 'ruta', 'driver'])
                     ->whereNull('fin');

        if ($tipoViaje) {
            $query->where('tipo_viaje', $tipoViaje);
        }

        $activeTrips = $query->get();

        return response()->json([
            'trips' => $activeTrips
        ]);
    }
}
