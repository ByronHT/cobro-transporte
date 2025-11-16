<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\RefundRequest;
use App\Models\RefundVerification;
use App\Models\Transaction;
use App\Models\Card;
use App\Models\Trip;
use App\Models\User;
use App\Models\PaymentEvent;
use App\Mail\RefundVerificationMail;

class RefundController extends Controller
{
    /**
     * Buscar transacciones por UID de tarjeta (para el chofer)
     * GET /api/driver/search-transactions?card_uid=XXX&trip_id=YYY
     */
    public function searchTransactionsByCardUid(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string',
            'trip_id' => 'required|exists:trips,id',
        ]);

        $cardUid = $request->card_uid;
        $tripId = $request->trip_id;

        // Buscar la tarjeta
        $card = Card::where('uid', $cardUid)->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Tarjeta no encontrada'
            ], 404);
        }

        // Obtener transacciones de esa tarjeta en este viaje
        $transactions = Transaction::where('card_id', $card->id)
            ->where('trip_id', $tripId)
            ->where('type', 'fare')
            ->with(['card.passenger'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'card_uid' => $transaction->card->uid,
                    'passenger_name' => $transaction->card->passenger->name,
                    'passenger_email' => $transaction->card->passenger->email,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'description' => $transaction->description,
                ];
            });

        return response()->json([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'uid' => $card->uid,
                'passenger_name' => $card->passenger->name,
                'passenger_email' => $card->passenger->email,
                'balance' => $card->balance,
            ],
            'transactions' => $transactions,
            'total_transactions' => $transactions->count(),
        ]);
    }

    /**
     * Crear solicitud de devolución (chofer solicita devolver)
     * POST /api/driver/refund-requests
     */
    public function createRefundRequest(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $transaction = Transaction::with(['card.passenger', 'trip'])->findOrFail($request->transaction_id);

            // Verificar que la transacción sea de tipo 'fare'
            if ($transaction->type !== 'fare') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden devolver transacciones de tipo pasaje'
                ], 400);
            }

            // Verificar que no exista ya una solicitud pendiente o completada para esta transacción
            $existingRequest = RefundRequest::where('transaction_id', $transaction->id)
                ->whereIn('status', ['pending', 'verified', 'completed'])
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una solicitud de devolución para esta transacción',
                    'existing_request' => $existingRequest
                ], 409);
            }

            // Crear la solicitud de devolución
            $refundRequest = RefundRequest::create([
                'transaction_id' => $transaction->id,
                'trip_id' => $transaction->trip_id,
                'driver_id' => $transaction->driver_id,
                'passenger_id' => $transaction->card->passenger_id,
                'card_id' => $transaction->card_id,
                'amount' => $transaction->amount,
                'reason' => $request->reason,
                'card_uid' => $transaction->card->uid,
                'status' => 'pending',
                // El token y expires_at se generan automáticamente en el modelo
            ]);

            // Enviar email de verificación al pasajero
            $this->sendVerificationEmail($refundRequest);

            Log::info('Solicitud de devolución creada', [
                'refund_request_id' => $refundRequest->id,
                'transaction_id' => $transaction->id,
                'driver_id' => $transaction->driver_id,
                'passenger_id' => $transaction->card->passenger_id,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de devolución creada. Se ha enviado un email al pasajero para verificación.',
                'refund_request' => [
                    'id' => $refundRequest->id,
                    'status' => $refundRequest->status,
                    'amount' => $refundRequest->amount,
                    'expires_at' => $refundRequest->expires_at->format('Y-m-d H:i:s'),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear solicitud de devolución', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud de devolución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar email de verificación al pasajero
     */
    private function sendVerificationEmail(RefundRequest $refundRequest)
    {
        try {
            $passenger = $refundRequest->passenger;

            // URL de verificación (ajustar según tu dominio en producción)
            $verificationUrl = url("/api/refund/verify/{$refundRequest->verification_token}");

            // Datos para el email
            $emailData = [
                'passenger_name' => $passenger->name,
                'amount' => $refundRequest->amount,
                'reason' => $refundRequest->reason,
                'driver_name' => $refundRequest->driver->name,
                'verification_url' => $verificationUrl,
                'expires_at' => $refundRequest->expires_at->format('d/m/Y H:i'),
            ];

            // Enviar email (simulado por ahora con Log)
            // En producción, descomentar esta línea:
            // Mail::to($passenger->email)->send(new RefundVerificationMail($emailData));

            Log::info('Email de verificación enviado', [
                'passenger_email' => $passenger->email,
                'refund_request_id' => $refundRequest->id,
                'verification_url' => $verificationUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar email de verificación', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }

    /**
     * Verificar solicitud de devolución (el pasajero aprueba o rechaza)
     * GET /api/refund/verify/{token}?action=approve|reject&comments=...
     */
    public function verifyRefund(Request $request, $token)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            $refundRequest = RefundRequest::where('verification_token', $token)->firstOrFail();

            // Verificar que la solicitud esté pendiente
            if ($refundRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ya fue procesada anteriormente',
                    'status' => $refundRequest->status
                ], 400);
            }

            // Verificar que no haya expirado
            if ($refundRequest->isExpired()) {
                $refundRequest->update(['status' => 'cancelled']);

                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ha expirado (válida por 24 horas)'
                ], 410);
            }

            // Crear registro de verificación
            $verification = RefundVerification::create([
                'refund_request_id' => $refundRequest->id,
                'action' => $request->action === 'approve' ? 'approved' : 'rejected',
                'user_id' => $refundRequest->passenger_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'comments' => $request->comments,
            ]);

            if ($request->action === 'approve') {
                // Actualizar estado a 'verified'
                $refundRequest->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);

                // Crear evento de notificación para el chofer
                $this->notifyDriverOfVerification($refundRequest, 'approved');

                return response()->json([
                    'success' => true,
                    'message' => 'Devolución aprobada. El chofer ha sido notificado y procesará la devolución.',
                    'refund_request' => [
                        'id' => $refundRequest->id,
                        'status' => $refundRequest->status,
                        'amount' => $refundRequest->amount,
                    ]
                ]);

            } else {
                // Rechazar la solicitud
                $refundRequest->update(['status' => 'rejected']);

                $this->notifyDriverOfVerification($refundRequest, 'rejected');

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud de devolución rechazada.',
                    'refund_request' => [
                        'id' => $refundRequest->id,
                        'status' => $refundRequest->status,
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al verificar devolución', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la verificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notificar al chofer sobre la verificación del pasajero
     */
    private function notifyDriverOfVerification(RefundRequest $refundRequest, $action)
    {
        try {
            // Crear PaymentEvent para notificación en tiempo real
            PaymentEvent::create([
                'trip_id' => $refundRequest->trip_id,
                'card_uid' => $refundRequest->card_uid,
                'card_id' => $refundRequest->card_id,
                'passenger_id' => $refundRequest->passenger_id,
                'event_type' => 'refund_' . $action, // 'refund_approved' o 'refund_rejected'
                'amount' => $refundRequest->amount,
                'message' => $action === 'approved'
                    ? "El pasajero aprobó la devolución de {$refundRequest->amount} Bs. Puede procesar la devolución ahora."
                    : "El pasajero rechazó la solicitud de devolución de {$refundRequest->amount} Bs.",
            ]);

            Log::info('Notificación de verificación enviada al chofer', [
                'refund_request_id' => $refundRequest->id,
                'action' => $action,
                'driver_id' => $refundRequest->driver_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar al chofer', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }

    /**
     * Procesar devolución (el chofer ejecuta la devolución después de la verificación)
     * POST /api/driver/process-refund/{refundRequestId}
     */
    public function processRefund(Request $request, $refundRequestId)
    {
        try {
            $refundRequest = RefundRequest::with(['transaction', 'card', 'passenger', 'driver', 'trip'])
                ->findOrFail($refundRequestId);

            // Verificar que la solicitud esté verificada
            if ($refundRequest->status !== 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud no ha sido verificada por el pasajero',
                    'current_status' => $refundRequest->status
                ], 400);
            }

            // Verificar que el chofer autenticado sea el dueño de la solicitud
            if ($request->user() && $request->user()->id !== $refundRequest->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para procesar esta devolución'
                ], 403);
            }

            DB::beginTransaction();

            try {
                $card = $refundRequest->card;
                $driver = $refundRequest->driver;
                $amount = $refundRequest->amount;

                // 1. Devolver saldo a la tarjeta del pasajero
                $card->balance += $amount;
                $card->save();

                // 2. Descontar del balance del chofer
                $driver->balance -= $amount;
                $driver->save();

                // 3. Crear transacción de tipo 'refund'
                $refundTransaction = Transaction::create([
                    'type' => 'refund',
                    'amount' => $amount,
                    'card_id' => $card->id,
                    'driver_id' => $driver->id,
                    'bus_id' => $refundRequest->trip->bus_id,
                    'ruta_id' => $refundRequest->trip->ruta_id,
                    'trip_id' => $refundRequest->trip_id,
                    'description' => "Devolución: {$refundRequest->reason}",
                ]);

                // 4. Crear PaymentEvent para notificar al pasajero
                PaymentEvent::create([
                    'trip_id' => $refundRequest->trip_id,
                    'card_uid' => $card->uid,
                    'card_id' => $card->id,
                    'passenger_id' => $refundRequest->passenger_id,
                    'event_type' => 'refund_completed',
                    'amount' => $amount,
                    'message' => "Se ha procesado la devolución de {$amount} Bs a su tarjeta. Motivo: {$refundRequest->reason}",
                ]);

                // 5. Actualizar estado de la solicitud
                $refundRequest->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // 6. NO agregar devolución al reporte del viaje
                // Las devoluciones se manejan en módulo separado
                // COMENTADO: Ya no agregamos info de devolución al campo reporte
                /*
                $trip = $refundRequest->trip;
                $currentReport = $trip->reporte ?? 'Viaje concluido sin novedades';

                $refundReport = "\n[DEVOLUCIÓN PROCESADA - " . now()->format('d/m/Y H:i') . "]\n";
                $refundReport .= "Monto: {$amount} Bs\n";
                $refundReport .= "Pasajero: {$refundRequest->passenger->name}\n";
                $refundReport .= "Motivo: {$refundRequest->reason}\n";
                $refundReport .= "Transacción original ID: {$refundRequest->transaction_id}\n";
                $refundReport .= "Transacción de devolución ID: {$refundTransaction->id}\n";
                $refundReport .= str_repeat('-', 50);

                $trip->update([
                    'reporte' => $currentReport . $refundReport
                ]);
                */

                DB::commit();

                Log::info('Devolución procesada exitosamente', [
                    'refund_request_id' => $refundRequest->id,
                    'refund_transaction_id' => $refundTransaction->id,
                    'amount' => $amount,
                    'card_id' => $card->id,
                    'driver_id' => $driver->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Devolución procesada exitosamente',
                    'refund' => [
                        'id' => $refundRequest->id,
                        'amount' => $amount,
                        'passenger_name' => $refundRequest->passenger->name,
                        'new_card_balance' => $card->balance,
                        'new_driver_balance' => $driver->balance,
                        'refund_transaction_id' => $refundTransaction->id,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error al procesar devolución', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'refund_request_id' => $refundRequestId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la devolución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener solicitudes de devolución pendientes del chofer
     * GET /api/driver/refund-requests
     */
    public function getDriverRefundRequests(Request $request)
    {
        try {
            $driver = $request->user();

            $refundRequests = RefundRequest::where('driver_id', $driver->id)
                ->with(['transaction', 'passenger', 'card', 'trip', 'verification'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($refund) {
                    return [
                        'id' => $refund->id,
                        'status' => $refund->status,
                        'amount' => $refund->amount,
                        'reason' => $refund->reason,
                        'passenger_name' => $refund->passenger->name,
                        'passenger' => [
                            'id' => $refund->passenger->id,
                            'name' => $refund->passenger->name,
                            'email' => $refund->passenger->email,
                        ],
                        'card_uid' => $refund->card_uid,
                        'trip_id' => $refund->trip_id,
                        'created_at' => $refund->created_at->format('Y-m-d H:i:s'),
                        'verified_at' => $refund->verified_at?->format('Y-m-d H:i:s'),
                        'completed_at' => $refund->completed_at?->format('Y-m-d H:i:s'),
                        'expires_at' => $refund->expires_at?->format('Y-m-d H:i:s'),
                        'is_expired' => $refund->isExpired(),
                        'can_process' => $refund->status === 'verified' && !$refund->isExpired(),
                        'is_reversed' => $refund->is_reversed ?? false,
                        'reversal_reason' => $refund->reversal_reason,
                        'reversed_at' => $refund->reversed_at?->format('Y-m-d H:i:s'),
                        'reversed_by' => $refund->reversed_by,
                    ];
                });

            return response()->json([
                'success' => true,
                'refund_requests' => $refundRequests,
                'summary' => [
                    'total' => $refundRequests->count(),
                    'pending' => $refundRequests->where('status', 'pending')->count(),
                    'verified' => $refundRequests->where('status', 'verified')->count(),
                    'completed' => $refundRequests->where('status', 'completed')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener solicitudes de devolución del chofer', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes de devolución'
            ], 500);
        }
    }

    /**
     * Obtener solicitudes de devolución del pasajero
     * GET /api/passenger/refund-requests
     */
    public function getPassengerRefundRequests(Request $request)
    {
        try {
            $passenger = $request->user();

            $refundRequests = RefundRequest::where('passenger_id', $passenger->id)
                ->with(['transaction', 'driver', 'card', 'trip', 'verification'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($refund) {
                    return [
                        'id' => $refund->id,
                        'transaction_id' => $refund->transaction_id,
                        'status' => $refund->status,
                        'amount' => $refund->amount,
                        'reason' => $refund->reason,
                        'driver_name' => $refund->driver->name,
                        'card_uid' => $refund->card_uid,
                        'trip_info' => [
                            'ruta' => $refund->trip->ruta->nombre ?? 'N/A',
                            'bus_plate' => $refund->trip->bus->plate ?? 'N/A',
                        ],
                        'created_at' => $refund->created_at->format('Y-m-d H:i:s'),
                        'verified_at' => $refund->verified_at?->format('Y-m-d H:i:s'),
                        'completed_at' => $refund->completed_at?->format('Y-m-d H:i:s'),
                        'expires_at' => $refund->expires_at?->format('Y-m-d H:i:s'),
                        'is_expired' => $refund->isExpired(),
                        'needs_verification' => $refund->status === 'pending' && !$refund->isExpired(),
                    ];
                });

            return response()->json([
                'success' => true,
                'refund_requests' => $refundRequests,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener solicitudes de devolución del pasajero', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes de devolución'
            ], 500);
        }
    }

    /**
     * Crear solicitud de devolución (pasajero solicita devolver una transacción)
     * POST /api/passenger/request-refund
     */
    public function createRefundRequestByPassenger(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $passenger = $request->user();
            $transaction = Transaction::with(['card.passenger', 'trip.driver', 'trip.bus', 'trip.ruta'])->findOrFail($request->transaction_id);

            // Verificar que la transacción pertenezca a una tarjeta del pasajero
            if ($transaction->card->passenger_id !== $passenger->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta transacción no te pertenece'
                ], 403);
            }

            // Verificar que la transacción sea de tipo 'fare'
            if ($transaction->type !== 'fare') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden solicitar devoluciones de transacciones de tipo pasaje'
                ], 400);
            }

            // Verificar que no exista ya una solicitud para esta transacción
            $existingRequest = RefundRequest::where('transaction_id', $transaction->id)
                ->whereIn('status', ['pending', 'verified', 'completed'])
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una solicitud de devolución para esta transacción',
                    'existing_request' => $existingRequest
                ], 409);
            }

            // Verificar que la transacción tenga driver_id
            $driverId = $transaction->driver_id ?? $transaction->trip->driver_id ?? null;

            if (!$driverId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede procesar la devolución: transacción sin chofer asignado'
                ], 400);
            }

            // Crear la solicitud de devolución
            $refundRequest = RefundRequest::create([
                'transaction_id' => $transaction->id,
                'trip_id' => $transaction->trip_id,
                'driver_id' => $driverId,
                'passenger_id' => $passenger->id,
                'card_id' => $transaction->card_id,
                'amount' => $transaction->amount,
                'reason' => $request->reason,
                'card_uid' => $transaction->card->uid,
                'status' => 'pending',
            ]);

            // Crear evento de notificación para el chofer
            PaymentEvent::create([
                'trip_id' => $transaction->trip_id,
                'card_uid' => $transaction->card->uid,
                'card_id' => $transaction->card_id,
                'passenger_id' => $passenger->id,
                'event_type' => 'refund_requested',
                'amount' => $transaction->amount,
                'message' => "El pasajero {$passenger->name} solicita devolución de {$transaction->amount} Bs. Motivo: {$request->reason}",
            ]);

            Log::info('Solicitud de devolución creada por pasajero', [
                'refund_request_id' => $refundRequest->id,
                'transaction_id' => $transaction->id,
                'passenger_id' => $passenger->id,
                'driver_id' => $transaction->driver_id,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de devolución enviada. El chofer revisará tu solicitud.',
                'refund_request' => [
                    'id' => $refundRequest->id,
                    'status' => $refundRequest->status,
                    'amount' => $refundRequest->amount,
                    'expires_at' => $refundRequest->expires_at->format('Y-m-d H:i:s'),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear solicitud de devolución por pasajero', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud de devolución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar o rechazar solicitud de devolución (desde dashboard del chofer)
     * POST /api/driver/approve-refund/{refundRequestId}
     */
    public function approveOrRejectRefund(Request $request, $refundRequestId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            $driver = $request->user();
            $refundRequest = RefundRequest::with(['transaction', 'driver', 'card', 'passenger', 'trip'])
                ->findOrFail($refundRequestId);

            // Verificar que la solicitud pertenezca al chofer autenticado
            if ($refundRequest->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para procesar esta solicitud'
                ], 403);
            }

            // Verificar que la solicitud esté pendiente
            if ($refundRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ya fue procesada anteriormente',
                    'status' => $refundRequest->status
                ], 400);
            }

            // Verificar que no haya expirado
            if ($refundRequest->isExpired()) {
                $refundRequest->update(['status' => 'cancelled']);

                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ha expirado (válida por 24 horas)'
                ], 410);
            }

            // Crear registro de verificación
            $verification = RefundVerification::create([
                'refund_request_id' => $refundRequest->id,
                'action' => $request->action === 'approve' ? 'approved' : 'rejected',
                'user_id' => $driver->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'comments' => $request->comments,
            ]);

            if ($request->action === 'approve') {
                // Procesar devolución automáticamente
                DB::beginTransaction();

                try {
                    $card = $refundRequest->card;
                    $amount = $refundRequest->amount;

                    // 1. Devolver saldo a la tarjeta del pasajero
                    $card->balance += $amount;
                    $card->save();

                    // 2. Descontar del balance del chofer
                    $driver->balance -= $amount;
                    $driver->save();

                    // 3. Crear transacción de tipo 'refund' (negativa para el chofer, representa la devolución)
                    $refundTransaction = Transaction::create([
                        'type' => 'refund',
                        'amount' => -$amount, // Negativo porque es una devolución (sale del chofer)
                        'card_id' => $card->id,
                        'driver_id' => $driver->id,
                        'bus_id' => $refundRequest->trip->bus_id,
                        'ruta_id' => $refundRequest->trip->ruta_id,
                        'trip_id' => $refundRequest->trip_id,
                        'description' => "Devolución aprobada: {$refundRequest->reason}",
                    ]);

                    // 4. Actualizar estado de la solicitud a 'completed'
                    $refundRequest->update([
                        'status' => 'completed',
                        'verified_at' => now(),
                        'completed_at' => now(),
                    ]);

                    // 5. Crear evento de notificación para el pasajero
                    PaymentEvent::create([
                        'trip_id' => $refundRequest->trip_id,
                        'card_uid' => $card->uid,
                        'card_id' => $card->id,
                        'passenger_id' => $refundRequest->passenger_id,
                        'event_type' => 'refund_approved',
                        'amount' => $amount,
                        'message' => "✅ Tu solicitud de devolución fue aprobada. Se han devuelto {$amount} Bs a tu tarjeta.",
                    ]);

                    // 6. NO agregar devolución al reporte del viaje
                    // Las devoluciones se manejan en módulo separado
                    // COMENTADO: Ya no agregamos info de devolución al campo reporte
                    /*
                    $trip = $refundRequest->trip;
                    $currentReport = $trip->reporte ?? 'Viaje concluido sin novedades';

                    $refundReport = "\n[DEVOLUCIÓN APROBADA - " . now()->format('d/m/Y H:i') . "]\n";
                    $refundReport .= "Monto: {$amount} Bs\n";
                    $refundReport .= "Pasajero: {$refundRequest->passenger->name}\n";
                    $refundReport .= "Motivo: {$refundRequest->reason}\n";
                    $refundReport .= "Transacción original ID: {$refundRequest->transaction_id}\n";
                    $refundReport .= "Transacción de devolución ID: {$refundTransaction->id}\n";
                    $refundReport .= str_repeat('-', 50);

                    $trip->update([
                        'reporte' => $currentReport . $refundReport
                    ]);
                    */

                    DB::commit();

                    Log::info('Devolución aprobada y procesada por chofer', [
                        'refund_request_id' => $refundRequest->id,
                        'driver_id' => $driver->id,
                        'amount' => $amount,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Solicitud aprobada y devolución procesada exitosamente',
                        'refund_request' => [
                            'id' => $refundRequest->id,
                            'status' => $refundRequest->status,
                            'amount' => $amount,
                            'new_driver_balance' => $driver->balance,
                        ]
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            } else {
                // Rechazar la solicitud
                $refundRequest->update(['status' => 'rejected']);

                // Crear evento de notificación para el pasajero
                PaymentEvent::create([
                    'trip_id' => $refundRequest->trip_id,
                    'card_uid' => $refundRequest->card_uid,
                    'card_id' => $refundRequest->card_id,
                    'passenger_id' => $refundRequest->passenger_id,
                    'event_type' => 'refund_rejected',
                    'amount' => $refundRequest->amount,
                    'message' => "❌ Tu solicitud de devolución fue rechazada por el chofer." . ($request->comments ? " Motivo: {$request->comments}" : ""),
                ]);

                Log::info('Devolución rechazada por chofer', [
                    'refund_request_id' => $refundRequest->id,
                    'driver_id' => $driver->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud de devolución rechazada.',
                    'refund_request' => [
                        'id' => $refundRequest->id,
                        'status' => $refundRequest->status,
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al procesar solicitud de devolución', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequestId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
