<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\BusCommand;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DriverActionController extends Controller
{
    /**
     * La app del chofer llama a este método para solicitar el inicio de un viaje.
     * El servidor guarda el comando en caché para que el dispositivo lo recoja.
     */
    public function requestTripStart(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id'
        ]);

        $driver = $request->user();
        $busId = $request->bus_id;

        // Verificar que el bus no tenga un viaje activo
        $activeTripForBus = Trip::where('bus_id', $busId)->whereNull('fin')->first();
        if ($activeTripForBus) {
            return response()->json(['message' => 'Este bus ya tiene un viaje activo.'], 409);
        }

        // Verificar que el chofer no tenga un viaje activo
        $activeTripForDriver = Trip::where('driver_id', $driver->id)->whereNull('fin')->first();
        if ($activeTripForDriver) {
            return response()->json(['message' => 'Este chofer ya tiene un viaje activo.'], 409);
        }

        // Obtener el bus y su ruta
        $bus = Bus::find($busId);

        // ✅ CREAR EL TRIP INMEDIATAMENTE (antes de que el Arduino pregunte)
        $trip = Trip::create([
            'fecha' => now(),
            'ruta_id' => $bus->ruta_id,
            'bus_id' => $busId,
            'driver_id' => $driver->id,
            'inicio' => now(),
        ]);

        // Crear el comando en la base de datos
        BusCommand::create([
            'bus_id' => $busId,
            'command' => 'start_trip',
            'status' => 'pending',
            'requested_by' => $driver->id
        ]);

        return response()->json([
            'message' => 'Viaje iniciado exitosamente.',
            'bus_id' => $busId,
            'driver_id' => $driver->id,
            'trip_id' => $trip->id
        ]);
    }

    /**
     * La app del chofer llama a este método para solicitar el fin de un viaje.
     */
    public function requestTripEnd(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'reporte' => 'nullable|string|max:2000'
        ]);

        $driver = $request->user();
        $busId = $request->bus_id;

        // Verificar que el chofer tenga un viaje activo
        $activeTrip = Trip::where('driver_id', $driver->id)
                         ->where('bus_id', $busId)
                         ->whereNull('fin')
                         ->first();

        if (!$activeTrip) {
            return response()->json(['message' => 'No tienes un viaje activo en este bus.'], 404);
        }

        // ✅ FINALIZAR EL VIAJE INMEDIATAMENTE
        $activeTrip->fin = now();

        // Si hay reporte, guardarlo
        if ($request->has('reporte') && !empty($request->reporte)) {
            $activeTrip->reporte = $request->reporte;
        }

        $activeTrip->save();

        // Crear el comando para que el Arduino también se entere
        $command = BusCommand::create([
            'bus_id' => $busId,
            'command' => 'end_trip',
            'status' => 'pending',
            'requested_by' => $driver->id
        ]);

        return response()->json([
            'message' => 'Viaje finalizado exitosamente.',
            'command_id' => $command->id,
            'trip' => $activeTrip
        ]);
    }

    /**
     * Procesa un pago realizado por el chofer.
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'card_id' => 'required|integer|exists:cards,id',
            'amount' => 'required|numeric|min:0.01',
            'trip_id' => 'required|integer|exists:trips,id', // Ensure trip is active
        ]);

        $card = Card::find($request->card_id);
        if (!$card) {
            return response()->json(['message' => 'Tarjeta no encontrada.'], 404);
        }

        if ($card->balance < $request->amount) {
            return response()->json(['message' => 'Saldo insuficiente en la tarjeta.'], 400);
        }

        $trip = Trip::where('id', $request->trip_id)
                    ->whereNull('fin') // Ensure it's an active trip
                    ->first();

        if (!$trip) {
            return response()->json(['message' => 'Viaje activo no encontrado para esta transacción.'], 404);
        }

        // Deduct amount from card
        $card->balance -= $request->amount;
        $card->save();

        // Create transaction
        $transaction = Transaction::create([
            'trip_id' => $trip->id,
            'card_id' => $card->id,
            'driver_id' => $trip->driver_id,
            'bus_id' => $trip->bus_id,
            'ruta_id' => $trip->ruta_id,
            'amount' => $request->amount,
            'type' => 'fare', // Changed from 'debit' to 'fare' for passenger payment
            'description' => 'Pago de pasaje',
        ]);

        // Update driver's balance (assuming driver is associated with the trip)
        $driver = $trip->driver; // Get the driver from the trip
        if ($driver) {
            $driver->balance += $request->amount;
            $driver->save();
        }

        return response()->json([
            'message' => 'Pago procesado exitosamente.',
            'transaction' => $transaction,
            'card_new_balance' => $card->balance,
            'driver_new_balance' => $driver ? $driver->balance : null,
        ], 201);
    }

    /**
     * Revertir una devolución (solo choferes)
     */
    public function reverseRefund(Request $request)
    {
        $request->validate([
            'refund_request_id' => 'required|exists:refund_requests,id',
            'reversal_reason' => 'required|string|min:10'
        ]);

        $refundRequest = \App\Models\RefundRequest::with(['card', 'passenger', 'driver', 'trip', 'transaction'])->findOrFail($request->refund_request_id);

        // Verificar que la devolución esté completada y no haya sido ya revertida
        if ($refundRequest->status !== 'completed') {
            return response()->json(['error' => 'Solo se pueden revertir devoluciones completadas.'], 400);
        }

        if ($refundRequest->is_reversed) {
            return response()->json(['error' => 'Esta devolución ya fue revertida.'], 400);
        }

        // Verificar que el chofer sea el dueño del viaje
        if ($refundRequest->trip->driver_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado para revertir esta devolución.'], 403);
        }

        DB::beginTransaction();
        try {
            $amount = $refundRequest->amount;
            $card = $refundRequest->card;
            $passenger = $refundRequest->passenger;
            $driver = $refundRequest->driver;

            // Revertir saldos
            $card->balance -= $amount; // Quitar el dinero devuelto
            $card->save();

            $passenger->balance -= $amount;
            $passenger->save();

            $driver->balance += $amount; // Devolver al chofer
            $driver->save();

            // Nota: saldo_actual no existe en la tabla trips
            // El saldo del viaje se calcula dinámicamente desde transactions
            // por lo tanto no es necesario actualizarlo aquí

            // Crear transacción de reversa (POSITIVA porque se está revirtiendo la devolución)
            $reversalTransaction = \App\Models\Transaction::create([
                'trip_id' => $refundRequest->trip_id,
                'card_id' => $card->id,
                'driver_id' => $driver->id,
                'passenger_id' => $passenger->id,
                'bus_id' => $refundRequest->trip->bus_id,
                'ruta_id' => $refundRequest->trip->ruta_id,
                'amount' => $amount, // Positivo porque el chofer recupera el dinero
                'type' => 'refund_reversal',
                'description' => 'Reversión de devolución: ' . $request->reversal_reason
            ]);

            // Marcar la devolución como revertida
            $refundRequest->update([
                'is_reversed' => true,
                'reversal_reason' => $request->reversal_reason,
                'reversed_at' => now(),
                'reversed_by' => auth()->id()
            ]);

            // Crear evento de notificación
            \App\Models\PaymentEvent::create([
                'trip_id' => $refundRequest->trip_id,
                'card_id' => $card->id,
                'card_uid' => $card->uid,
                'passenger_id' => $passenger->id,
                'amount' => $amount,
                'event_type' => 'refund_reversed',
                'message' => "Devolución revertida: {$amount} Bs - Razón: {$request->reversal_reason}"
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Devolución revertida exitosamente.',
                'refund_request' => $refundRequest->fresh(),
                'reversal_transaction' => $reversalTransaction,
                'card_new_balance' => $card->balance,
                'driver_new_balance' => $driver->balance,
                'passenger_new_balance' => $passenger->balance
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al revertir la devolución: ' . $e->getMessage()], 500);
        }
    }

}
