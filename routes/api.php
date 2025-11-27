<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\TripController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\DriverActionController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\BusController;
use App\Http\Controllers\API\RefundController;
use App\Http\Controllers\API\BusTrackingController;
use App\Http\Controllers\API\ComplaintController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TurnoController;

Route::post('/payment/process', [PaymentController::class, 'process']);
Route::post('/cliente/login', [LoginController::class, 'loginCliente']);

// Nuevas rutas de autenticación con código de 4 dígitos
Route::post('/auth/login-code', [AuthController::class, 'loginWithCode']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas para el dispositivo ESP8266 (públicas - sin autenticación)
Route::post('/trips/start', [TripController::class, 'start']);
Route::post('/trips/end', [TripController::class, 'end']);
Route::post('/trips/end-by-bus', [TripController::class, 'endByBus']);
Route::get('/device/command/{bus}', [DeviceController::class, 'getCommand']);
Route::post('/device/command/{commandId}/complete', [DeviceController::class, 'markCommandAsCompleted']);
Route::post('/device/command/{commandId}/fail', [DeviceController::class, 'markCommandAsFailed']);

// Rutas públicas para verificación de devoluciones (el pasajero accede desde email)
Route::get('/refund/verify/{token}', [RefundController::class, 'verifyRefund']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/profile', [UserController::class,'profile']);
    Route::get('/transactions', [TransactionController::class,'index']);
    Route::get('/recharges', [TransactionController::class,'recharges']);
    Route::get('/trips', [TripController::class,'userTrips']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Rutas del chofer - requieren autenticación
    Route::post('/driver/request-trip-start', [DriverActionController::class, 'requestTripStart']);
    Route::post('/driver/request-trip-end', [DriverActionController::class, 'requestTripEnd']);
    Route::post('/driver/process-payment', [DriverActionController::class, 'processPayment']);
    Route::get('/driver/buses', [BusController::class, 'index']);
    Route::get('/driver/current-trip-status', [TripController::class, 'currentTripStatus']);
    Route::get('/driver/current-trip-transactions', [TripController::class, 'currentTripTransactions']);
    Route::get('/driver/current-trip-payment-events', [TripController::class, 'currentTripPaymentEvents']);
    Route::post('/driver/update-trip-report', [TripController::class, 'updateTripReport']);

    // Nuevas rutas para sistema de turnos
    Route::post('/driver/turno/start', [TurnoController::class, 'startTurno']);
    Route::post('/driver/turno/finish', [TurnoController::class, 'finishTurno']);
    Route::get('/driver/turno/active', [TurnoController::class, 'getActiveTurno']);
    Route::get('/driver/turno/historial', [TurnoController::class, 'getHistorial']);
    Route::get('/driver/buses/disponibles', [TurnoController::class, 'getBusesDisponibles']);

    // Nuevas rutas para viajes con ida/vuelta
    Route::post('/driver/trip/start-with-turno', [TripController::class, 'startWithTurno']);
    Route::post('/driver/trip/finish', [TripController::class, 'finishTrip']);
    Route::post('/driver/trip/save-waypoint', [TripController::class, 'saveWaypoint']);

    // Rutas del pasajero
    Route::get('/passenger/payment-events', [TripController::class, 'passengerPaymentEvents']);

    // Sistema de Devoluciones - Rutas del Chofer
    Route::get('/driver/search-transactions', [RefundController::class, 'searchTransactionsByCardUid']);
    Route::get('/driver/refund-requests', [RefundController::class, 'getDriverRefundRequests']);
    Route::post('/driver/approve-refund/{refundRequestId}', [RefundController::class, 'approveOrRejectRefund']);
    Route::post('/driver/reverse-refund', [DriverActionController::class, 'reverseRefund']);

    // Sistema de Devoluciones - Rutas del Pasajero
    Route::post('/passenger/request-refund', [RefundController::class, 'createRefundRequestByPassenger']);
    Route::get('/passenger/refund-requests', [RefundController::class, 'getPassengerRefundRequests']);

    // Sistema de Tracking GPS - Rutas del Chofer
    Route::post('/driver/update-location', [BusTrackingController::class, 'updateLocation']);

    // Sistema de Tracking GPS - Rutas del Pasajero
    Route::get('/passenger/nearby-buses', [BusTrackingController::class, 'getNearbyBuses']);
    Route::get('/passenger/available-routes', [BusTrackingController::class, 'getAvailableRoutes']);
    Route::get('/passenger/route-details/{id}', [BusTrackingController::class, 'getRouteDetails']);
    Route::get('/passenger/bus-location/{busId}', [BusTrackingController::class, 'getBusLocation']);
    Route::get('/passenger/active-buses', [TripController::class, 'getActiveBusesWithType']);

    // Sistema de Quejas - Rutas del Pasajero
    Route::get('/passenger/routes', [ComplaintController::class, 'getRoutes']);
    Route::get('/passenger/drivers-by-route/{routeId}', [ComplaintController::class, 'getDriversByRoute']);
    Route::get('/passenger/transactions-for-complaints', [ComplaintController::class, 'getPassengerTransactions']);
    Route::post('/passenger/complaints', [ComplaintController::class, 'store']);
    Route::get('/passenger/my-complaints', [ComplaintController::class, 'getMyComplaints']);

    // Sistema de Quejas - Rutas del Admin
    Route::get('/admin/complaints', [ComplaintController::class, 'getAllComplaints']);
    Route::put('/admin/complaints/{id}/status', [ComplaintController::class, 'updateStatus']);
});