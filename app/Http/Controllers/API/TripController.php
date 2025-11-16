<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Transaction;
use App\Models\PaymentEvent;

class TripController extends Controller
{
    public function userTrips(Request $request)
    {
        $user = $request->user();
        $per = intval($request->get('per', 20));

        // Obtener los IDs de las tarjetas del usuario
        $cardIds = $user->cards()->pluck('id')->toArray();

        // Obtener TODAS las transacciones de tipo 'fare' (cada pago es una entrada)
        // No agrupamos por viaje, cada transacción aparece individualmente
        $transactions = Transaction::with(['trip.ruta', 'trip.bus', 'trip.driver'])
            ->whereIn('card_id', $cardIds)
            ->where('type', 'fare')
            ->whereNotNull('trip_id')
            ->orderBy('created_at', 'desc')
            ->paginate($per);

        // Transformar las transacciones para que tengan el formato esperado por el frontend
        $transactions->getCollection()->transform(function ($transaction) {
            $trip = $transaction->trip;

            // Crear un objeto que simule un viaje pero con datos de la transacción
            return (object) [
                'id' => $trip->id,
                'transaction_id' => $transaction->id,
                'fare' => $transaction->amount,
                // IMPORTANTE: Usar created_at de la TRANSACCIÓN (momento del pago)
                // NO usar trip->inicio (momento que empezó el viaje)
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

        // Verificar que el bus no tenga un viaje activo
        $activeTripForBus = Trip::where('bus_id', $request->bus_id)->whereNull('fin')->first();
        if ($activeTripForBus) {
            return response()->json(['message' => 'Este bus ya tiene un viaje activo.'], 409);
        }

        // Verificar que el chofer no tenga un viaje activo
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

        // Si el chofer proporciona un reporte personalizado, se usa
        // Si no, se usa el valor por defecto de la columna
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

        // Buscar el viaje activo del bus
        $trip = Trip::where('bus_id', $request->bus_id)->whereNull('fin')->first();

        if (!$trip) {
            return response()->json(['message' => 'No se encontró un viaje activo para este bus.'], 404);
        }

        $trip->fin = now();

        // Si el chofer proporciona un reporte personalizado, se usa
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

        // Calcular el monto acumulado del viaje actual
        // - fare: positivo (ingreso por pago de pasajero)
        // - refund: negativo (descuenta cuando se devuelve dinero)
        // - refund_reversal: positivo (recupera dinero cuando se revierte una devolución)
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

        // Agregar el nombre del pasajero a cada transacción
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

        // Obtener el último ID de evento que el frontend ya procesó
        $lastEventId = $request->query('last_event_id', 0);

        // Consultar eventos nuevos desde el último ID
        $events = PaymentEvent::with(['passenger', 'card'])
            ->forTrip($activeTrip->id)
            ->where('id', '>', $lastEventId)
            ->recent()
            ->get();

        // Agregar nombre del pasajero si existe
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

        // Obtener los IDs de las tarjetas del usuario
        $cardIds = $user->cards()->pluck('id')->toArray();

        // Obtener el último ID de evento que el frontend ya procesó
        $lastEventId = $request->query('last_event_id', 0);

        // Consultar eventos de las tarjetas del usuario
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

        // Verificar que el usuario autenticado sea el chofer del viaje
        $driver = $request->user();
        if ($trip->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para modificar este viaje.'
            ], 403);
        }

        // Manejar la subida de foto si existe
        if ($request->hasFile('photo')) {
            // Si ya existe una foto previa, eliminarla
            if ($trip->photo_path && \Storage::disk('public')->exists($trip->photo_path)) {
                \Storage::disk('public')->delete($trip->photo_path);
            }

            $photoPath = $request->file('photo')->store('trip_reports', 'public');
            $trip->photo_path = $photoPath;
        }

        // Agregar el nuevo reporte al existente con timestamp
        $timestamp = now()->format('d/m/Y H:i');
        $newReport = "\n[INCIDENTE REGISTRADO - {$timestamp}]\n";
        $newReport .= $request->reporte . "\n";

        // Si el reporte es el valor por defecto, reemplazarlo
        if ($trip->reporte === 'Viaje concluido sin novedades') {
            $trip->reporte = "[INCIDENTE REGISTRADO - {$timestamp}]\n" . $request->reporte;
        } else {
            // Agregar al reporte existente
            $trip->reporte .= $newReport;
        }

        $trip->save();

        return response()->json([
            'success' => true,
            'message' => 'Reporte registrado exitosamente',
            'trip' => $trip
        ]);
    }
}
