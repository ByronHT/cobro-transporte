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

        $card = Card::where('uid', $request->uid)->where('active', true)->first();
        $trip = Trip::with(['bus.ruta', 'driver'])->where('id', $request->trip_id)->whereNull('fin')->first();

        // 1. Validar que el viaje esté activo
        if (!$trip) {
            return response()->json(['status' => 'error', 'message' => 'VIAJE_NO_ACTIVO'], 404);
        }

        // 2. Validar que el viaje esté bien configurado
        if (!$trip->driver || !$trip->bus || !$trip->bus->ruta || !$trip->bus->ruta->tarifa_base) {
            return response()->json(['status' => 'error', 'message' => 'VIAJE_MAL_CONFIGURADO'], 400);
        }

        $fare = $trip->bus->ruta->tarifa_base;
        $driver = $trip->driver;

        // 3. Validar la tarjeta del pasajero
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

            return response()->json(['status' => 'error', 'message' => 'TARJETA_INVALIDA'], 404);
        }

        // 3.5 Validar que la tarjeta esté activa
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

            return response()->json(['status' => 'error', 'message' => 'TARJETA_INACTIVA'], 403);
        }

        // 4. Validar Saldo
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

            return response()->json([
                'status' => 'success',
                'message' => 'PAGO_REALIZADO',
                'new_card_balance' => number_format($card->balance, 2),
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

            return response()->json(['status' => 'error', 'message' => 'ERROR_SERVIDOR'], 500);
        }
    }
}
