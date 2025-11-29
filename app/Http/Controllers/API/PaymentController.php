<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Card;
use App\Models\Trip;
use App\Models\Transaction;
use App\Models\PaymentEvent;

class PaymentController extends Controller
{
    /**
     * Procesa un pago de pasaje y lo asocia a un viaje activo.
     */
    public function process(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'trip_id' => 'required|integer|exists:trips,id'
        ]);

        // Buscar la tarjeta y cargar la relación con el usuario (pasajero)
        $card = Card::with('passenger')->where('uid', $request->uid)->first();
        $trip = Trip::with(['bus.ruta', 'driver'])->where('id', $request->trip_id)->whereNull('fin')->first();

        // 1. Validar que el viaje esté activo
        if (!$trip) {
            return response()->json([
                'status' => 'error',
                'message' => 'VIAJE_NO_ACTIVO',
                'display_message' => 'No hay viaje activo'
            ], 404);
        }

        // 2. Validar que el viaje esté bien configurado
        if (!$trip->driver || !$trip->bus || !$trip->bus->ruta || !$trip->bus->ruta->tarifa_base) {
            return response()->json([
                'status' => 'error',
                'message' => 'VIAJE_MAL_CONFIGURADO',
                'display_message' => 'Error de configuracion'
            ], 400);
        }

        // CALCULAR TARIFA según tipo de usuario
        $passenger = $card->passenger;
        $fare = $this->calculateFare($passenger, $trip->bus->ruta);
        $driver = $trip->driver;

        // 3. Validar la tarjeta del pasajero - TARJETA NO REGISTRADA
        if (!$card) {
            // Registrar evento de tarjeta inválida
            PaymentEvent::create([
                'trip_id' => $trip->id,
                'card_uid' => $request->uid,
                'card_id' => null,
                'passenger_id' => null,
                'event_type' => 'invalid_card',
                'amount' => null,
                'required_amount' => $fare,
                'message' => 'Tarjeta no registrada en el sistema. UID: ' . $request->uid
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'TARJETA_NO_REGISTRADA',
                'display_message' => 'Tarjeta no registrada'
            ], 404);
        }

        // 3.5 Validar que la tarjeta esté activa - TARJETA NO ACTIVA
        if (!$card->active) {
            // Registrar evento de tarjeta inactiva
            PaymentEvent::create([
                'trip_id' => $trip->id,
                'card_uid' => $request->uid,
                'card_id' => $card->id,
                'passenger_id' => $card->passenger_id,
                'event_type' => 'inactive_card',
                'amount' => $card->balance,
                'required_amount' => $fare,
                'message' => 'Tarjeta inactiva. Contacte al administrador.'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'TARJETA_NO_ACTIVA',
                'display_message' => 'Tarjeta no activa'
            ], 403);
        }

        // 4. Validar Saldo - SALDO INSUFICIENTE
        if ($card->balance < $fare) {
            // Registrar evento de saldo insuficiente
            PaymentEvent::create([
                'trip_id' => $trip->id,
                'card_uid' => $request->uid,
                'card_id' => $card->id,
                'passenger_id' => $card->passenger_id,
                'event_type' => 'insufficient_balance',
                'amount' => $card->balance,
                'required_amount' => $fare,
                'message' => 'Saldo insuficiente. Disponible: ' . number_format($card->balance, 2) . ' Bs. Requerido: ' . number_format($fare, 2) . ' Bs'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'SALDO_INSUFICIENTE',
                'display_message' => 'Saldo insuficiente',
                'current_balance' => number_format($card->balance, 2),
                'required_amount' => number_format($fare, 2)
            ], 402);
        }

        // 5. Procesar el pago y la ganancia de forma atómica
        try {
            DB::transaction(function () use ($card, $driver, $trip, $fare) {
                // Descontar saldo del pasajero (Card.balance)
                // NOTA: NO actualizamos passenger.balance (siempre es 0.00)
                // Ver docs/ARQUITECTURA_BALANCE.md
                $card->decrement('balance', $fare);

                // Incrementar saldo del chofer (User.balance = ganancias acumuladas)
                $driver->increment('balance', $fare);

                // Registrar la transacción de cobro
                Transaction::create([
                    'type' => 'fare', // tipo: cobro de pasaje
                    'amount' => $fare,
                    'card_id' => $card->id,
                    'driver_id' => $trip->driver_id,
                    'bus_id' => $trip->bus_id,
                    'ruta_id' => $trip->ruta_id,
                    'trip_id' => $trip->id,
                    'description' => 'Cobro de pasaje en ' . $trip->bus->ruta->nombre .
                                    ($trip->bus->ruta->descripcion ? ' - ' . $trip->bus->ruta->descripcion : '')
                ]);
            });

            // Registrar evento exitoso
            PaymentEvent::create([
                'trip_id' => $trip->id,
                'card_uid' => $request->uid,
                'card_id' => $card->id,
                'passenger_id' => $card->passenger_id,
                'event_type' => 'success',
                'amount' => $fare,
                'required_amount' => $fare,
                'message' => 'Pago realizado con éxito. Monto: ' . number_format($fare, 2) . ' Bs'
            ]);

            // Recargar la tarjeta con el balance actualizado
            $card->refresh();

            // Obtener nombre del pasajero
            $passengerName = $card->passenger ? $card->passenger->name : 'Pasajero';

            return response()->json([
                'status' => 'success',
                'message' => 'PAGO_REALIZADO',
                'display_message' => 'Pago exitoso',
                'passenger_name' => $passengerName,
                'new_balance' => number_format($card->balance, 2),
                'amount_charged' => number_format($fare, 2)
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en transacción de pago: ' . $e->getMessage());

            // Registrar evento de error del servidor
            try {
                PaymentEvent::create([
                    'trip_id' => $trip->id,
                    'card_uid' => $request->uid,
                    'card_id' => $card->id ?? null,
                    'passenger_id' => $card->passenger_id ?? null,
                    'event_type' => 'error',
                    'amount' => null,
                    'required_amount' => $fare,
                    'message' => 'Error en el servidor al procesar el pago: ' . $e->getMessage()
                ]);
            } catch (\Exception $logError) {
                // Si falla el registro del evento, solo loguearlo
                Log::error('Error registrando evento de pago: ' . $logError->getMessage());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'ERROR_SERVIDOR',
                'display_message' => 'Error del servidor'
            ], 500);
        }
    }

    /**
     * Calcula la tarifa correcta según el tipo de usuario
     * Todos los tipos especiales pagan 1.00 Bs
     */
    private function calculateFare($user, $ruta)
    {
        // Si el usuario no existe, cobrar tarifa normal
        if (!$user) {
            return $ruta->tarifa_base ?? 2.30;
        }

        // Tipos con tarifa de 1.00 Bs
        $discountedTypes = ['senior', 'minor', 'student_school', 'student_university'];

        if (in_array($user->user_type, $discountedTypes)) {
            return 1.00;
        }

        // Adulto regular paga tarifa base
        return $ruta->tarifa_base ?? 2.30;
    }
}
