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
use App\Models\TimeRecord; // Añado TimeRecord Model
use App\Models\Turno; // Añado Turno Model
use Carbon\Carbon; // Añado Carbon
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
            'bus_id' => 'required|integer|exists:buses,id',
            'tipo_viaje' => 'nullable|string|in:ida,vuelta'
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
            'tipo_viaje' => $request->tipo_viaje,
        ]);

        // Registrar el inicio del viaje en el TimeRecord
        if ($trip->tipo_viaje === 'ida') {
            TimeRecord::create([
                'driver_id' => $driver->id,
                'turno_id' => $this->getCurrentTurno($driver->id)->id ?? null,
                'trip_ida_id' => $trip->id,
                'inicio_ida' => now(),
                'estado' => 'en_curso',
            ]);
        } elseif ($trip->tipo_viaje === 'vuelta') {
            $recordToUpdate = TimeRecord::where('driver_id', $driver->id)
                ->where('turno_id', $this->getCurrentTurno($driver->id)->id ?? null)
                ->whereDate('created_at', Carbon::today())
                ->whereNotNull('fin_ida')
                ->whereNull('inicio_vuelta')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recordToUpdate) {
                $recordToUpdate->update([
                    'trip_vuelta_id' => $trip->id,
                    'inicio_vuelta' => now(),
                    'estado' => 'en_curso',
                ]);
            } else {
                TimeRecord::create([
                    'driver_id' => $driver->id,
                    'turno_id' => $this->getCurrentTurno($driver->id)->id ?? null,
                    'trip_vuelta_id' => $trip->id,
                    'inicio_vuelta' => now(),
                    'estado' => 'en_curso',
                ]);
            }
        }


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

        // Registrar el fin del viaje en el TimeRecord
        $driverId = $driver->id;
        $tipoViaje = $activeTrip->tipo_viaje;

        // Buscar el registro de horas que corresponda a este viaje.
        $record = TimeRecord::where('driver_id', $driverId)
            ->where(function ($query) use ($activeTrip) {
                $query->where('trip_ida_id', $activeTrip->id)
                      ->orWhere('trip_vuelta_id', $activeTrip->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record) {
            \Log::warning("No se encontró TimeRecord para Trip ID: {$activeTrip->id} en requestTripEnd de DriverActionController.");
        } else {
            $finReal = now();
            $updateData = [];

            if ($tipoViaje === 'ida' && $record->trip_ida_id === $activeTrip->id && !$record->fin_ida) {
                $updateData['fin_ida'] = $finReal;
                
                // Solo calcular si la fecha de inicio existe, para evitar errores.
                if ($record->inicio_ida) {
                    $tiempoReal = Carbon::parse($record->inicio_ida)->diffInMinutes($finReal);
                    $tiempoEstimado = 45; // Default
                    $retraso = $tiempoReal - $tiempoEstimado;
                    
                    if ($retraso > 5) {
                        $updateData['estado'] = 'retrasado';
                        $updateData['tiempo_retraso_minutos'] = $retraso;
                    } else {
                        $updateData['estado'] = 'normal';
                    }
                }
            } elseif ($tipoViaje === 'vuelta' && $record->trip_vuelta_id === $activeTrip->id && !$record->fin_vuelta_real) {
                $updateData['fin_vuelta_real'] = $finReal;

                // Solo calcular retraso si la fecha estimada existe, para evitar errores.
                if ($record->fin_vuelta_estimado) {
                    $llegadaEstimada = Carbon::parse($record->fin_vuelta_estimado);
                    $retraso = $finReal->diffInMinutes($llegadaEstimada, false); // negativo si llegó antes
                    
                    if ($retraso > 5) {
                        $updateData['estado'] = 'retrasado';
                        $updateData['tiempo_retraso_minutos'] = $retraso;
                    } else {
                        $updateData['estado'] = 'normal';
                    }
                } else {
                    $updateData['estado'] = 'normal'; // No se puede calcular retraso
                }

                // Marcar como último viaje si es después de las 9 PM
                if ($finReal->hour >= 21) {
                    $updateData['es_ultimo_viaje'] = true;
                }
            }

            if (!empty($updateData)) {
                $record->update($updateData);
            }
        }


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
     * Ahora calcula automáticamente la tarifa según el tipo de usuario.
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'card_id' => 'required|integer|exists:cards,id',
            'trip_id' => 'required|integer|exists:trips,id',
        ]);

        // Obtener tarjeta con usuario
        $card = Card::with('user')->find($request->card_id);
        if (!$card) {
            return response()->json(['message' => 'Tarjeta no encontrada.'], 404);
        }

        $user = $card->user;
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Obtener viaje activo con ruta
        $trip = Trip::with('ruta', 'driver')->where('id', $request->trip_id)
                    ->whereNull('fin')
                    ->first();

        if (!$trip) {
            return response()->json(['message' => 'Viaje activo no encontrado.'], 404);
        }

        // CALCULAR TARIFA CORRECTA según tipo de usuario
        $amount = $this->calculateFare($user, $trip->ruta);

        // Validar saldo suficiente
        if ($card->balance < $amount) {
            return response()->json(['message' => 'Saldo insuficiente en la tarjeta.'], 400);
        }

        // Descontar de la tarjeta
        $card->balance -= $amount;
        $card->save();

        // Crear transacción
        $transaction = Transaction::create([
            'trip_id' => $trip->id,
            'card_id' => $card->id,
            'driver_id' => $trip->driver_id,
            'bus_id' => $trip->bus_id,
            'ruta_id' => $trip->ruta_id,
            'amount' => $amount,
            'type' => 'fare',
            'description' => "Pasaje - {$user->user_type}",
        ]);

        // Actualizar balance del chofer
        $driver = $trip->driver;
        if ($driver) {
            $driver->balance += $amount;
            $driver->save();
        }

        // Crear evento de notificación
        \App\Models\PaymentEvent::create([
            'trip_id' => $trip->id,
            'card_id' => $card->id,
            'card_uid' => $card->uid,
            'passenger_id' => $user->id,
            'amount' => $amount,
            'event_type' => 'payment',
            'message' => "Cobro: {$amount} Bs ({$user->user_type})"
        ]);

        return response()->json([
            'message' => 'Pago procesado exitosamente.',
            'amount_charged' => $amount,
            'user_type' => $user->user_type,
            'transaction' => $transaction,
            'card_new_balance' => $card->balance,
            'driver_new_balance' => $driver ? $driver->balance : null,
        ], 201);
    }

    /**
     * Calcula la tarifa correcta según el tipo de usuario
     * Todos los tipos especiales pagan 1.00 Bs
     */
    private function calculateFare($user, $ruta)
    {
        // Tipos con tarifa de 1.00 Bs
        $discountedTypes = ['senior', 'minor', 'student_school', 'student_university'];

        if (in_array($user->user_type, $discountedTypes)) {
            return 1.00;
        }

        // Adulto regular paga tarifa base
        return $ruta->tarifa_base ?? 2.30;
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

    /**
     * Obtiene el turno activo actual para un chofer.
     *
     * @param int $driverId
     * @return \App\Models\Turno|null
     */
    private function getCurrentTurno(int $driverId)
    {
        return \App\Models\Turno::where('driver_id', $driverId)
            ->where('status', 'activo')
            ->whereDate('fecha', Carbon::today())
            ->first();
    }
}
