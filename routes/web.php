<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CardController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\RutaController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\DevolucionController;
use Illuminate\Support\Facades\Artisan;



// Ruta de inicio - Redirige a login para drivers/passengers (React)
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-select2', function () {
    return view('test');
});

// ===========================
// RUTAS DE LOGIN SEPARADAS
// ===========================

// Login ADMIN (Blade tradicional - Panel de Administración)
Route::get('/login-admin', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login-admin', [AuthController::class, 'login']);

// Login DRIVER (React - redirige a SPA)
Route::get('/login-driver', function () {
    return view('welcome'); // SPA de React maneja /login con tipo driver
})->name('login.driver');

// Login PASSENGER (React - redirige a SPA)
Route::get('/login-passenger', function () {
    return view('welcome'); // SPA de React maneja /login con tipo passenger
})->name('login.passenger');

// Logout general
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
use App\Http\Controllers\Admin\MonitoringController;

// Grupo protegido para administradores
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Dashboard principal
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // CRUD usuarios y tarjetas
        Route::resource('users', UserController::class);
        Route::resource('cards', CardController::class);
        Route::resource('buses', BusController::class);
        Route::resource('rutas', RutaController::class);
        Route::resource('trips', TripController::class);
        Route::resource('transactions', TransactionController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::resource('complaints', ComplaintController::class)->only(['index', 'update']);
        Route::post('cards/{card}/recharge', [CardController::class, 'recharge'])->name('cards.recharge');

        // Módulo de Reportes de Choferes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/{id}', [ReporteController::class, 'show'])->name('reportes.show');
        Route::patch('/reportes/{id}/marcar-atendido', [ReporteController::class, 'marcarAtendido'])->name('reportes.marcar-atendido');
        Route::post('/reportes/{id}/cambiar-estado', [ReporteController::class, 'cambiarEstado'])->name('reportes.cambiar-estado');

        // Módulo de Devoluciones
        Route::get('/devoluciones', [DevolucionController::class, 'index'])->name('devoluciones.index');
        Route::get('/devoluciones/{id}/edit', [DevolucionController::class, 'edit'])->name('devoluciones.edit');
        Route::put('/devoluciones/{id}', [DevolucionController::class, 'update'])->name('devoluciones.update');
        Route::delete('/devoluciones/{id}/revertir', [DevolucionController::class, 'revertir'])->name('devoluciones.revertir');

        // Monitoreo
        Route::get('/monitoring/trips', [MonitoringController::class, 'trips'])->name('monitoring.trips');
        Route::get('/monitoring/card-transactions', [MonitoringController::class, 'cardTransactions'])->name('monitoring.card_transactions');

        // Panel de Tiempo Real
        Route::get('/realtime', [App\Http\Controllers\Admin\RealtimeController::class, 'index'])->name('realtime');
        Route::get('/realtime/active-buses', [App\Http\Controllers\Admin\RealtimeController::class, 'getActiveBuses'])->name('realtime.active-buses');

        // AJAX endpoints para tablas
        Route::get('/ajax/dashboard/trips', [DashboardController::class, 'getTripsData'])->name('ajax.dashboard.trips');
        Route::get('/ajax/dashboard/transactions', [DashboardController::class, 'getTransactionsData'])->name('ajax.dashboard.transactions');
        Route::get('/ajax/trips', [TripController::class, 'getTripsData'])->name('ajax.trips');
        Route::get('/ajax/transactions', [TransactionController::class, 'getTransactionsData'])->name('ajax.transactions');
    });

// Ruta de perfil de usuario (legacy - puede que no se use)
Route::middleware('auth')->get('/usuario', function () {
    return view('user.profile');
});

// SPA Fallback Route MUST BE LAST: Catches all unhandled web routes and serves the React app
// IMPORTANT: Exclude 'admin' prefix entirely so admin routes middleware works
// This regex matches anything that doesn't start with 'admin'
Route::fallback(function () {
    return view('welcome');
});
Route::get('/generate-key', function () {
    \Artisan::call('key:generate');
    return 'APP_KEY generada exitosamente: ' . config('app.key');
});

Route::get('/cache-config', function () {
    Artisan::call('config:cache');
    return "✔ Config cache generado correctamente.";
});

Route::get('/cache-routes', function () {
    Artisan::call('route:cache');
    return "✔ Route cache generado correctamente.";
});

Route::get('/run-migrations', function () {
    Artisan::call('migrate --force');
    return "✔ Migraciones ejecutadas.";
});
