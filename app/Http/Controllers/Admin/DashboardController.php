<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Card;
use App\Models\Trip;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Parámetros de paginación manual
        $perPage = 8;
        $tripsPage = max(1, (int) $request->query('trips_page', 1));
        $transactionsPage = max(1, (int) $request->query('transactions_page', 1));

        // filtros para viajes
        $driverId = $request->query('driver_id');
        $date = $request->query('date');

        // filtros para transacciones
        $passengerId = $request->query('passenger_id');
        $transactionDate = $request->query('transaction_date');

        // datos para filtros UI
        $drivers = User::where('role', 'driver')->where('active', 1)->get();
        $passengers = User::where('role', 'passenger')->where('active', 1)->get();

        // query de trips: traemos ruta, bus, driver
        $tripsQuery = Trip::with(['ruta','bus','driver'])
            ->withSum('transactions as transactions_sum_amount', 'amount');

        if ($driverId) {
            $tripsQuery->where('driver_id', $driverId);
        }

        if ($date) {
            $tripsQuery->whereDate('fecha', $date);
        }

        // Contar total de viajes para paginación
        $totalTrips = $tripsQuery->count();
        $tripsHasMore = $totalTrips > ($tripsPage * $perPage);
        $tripsHasPrev = $tripsPage > 1;

        // Obtener viajes con paginación manual
        $trips = $tripsQuery->orderBy('fecha','desc')
            ->skip(($tripsPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        // query de transacciones
        $transactionsQuery = Transaction::with(['card.passenger', 'ruta', 'bus', 'driver']);

        if ($passengerId) {
            $transactionsQuery->whereHas('card', function($q) use ($passengerId) {
                $q->where('passenger_id', $passengerId);
            });
        }

        if ($transactionDate) {
            $transactionsQuery->whereDate('created_at', $transactionDate);
        }

        // Contar total de transacciones para paginación
        $totalTransactions = $transactionsQuery->count();
        $transactionsHasMore = $totalTransactions > ($transactionsPage * $perPage);
        $transactionsHasPrev = $transactionsPage > 1;

        // Obtener transacciones con paginación manual
        $transactions = $transactionsQuery->orderBy('created_at', 'desc')
            ->skip(($transactionsPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        // totales para resumen
        $totalUsuarios = User::where('active',1)->count();
        $totalPasajeros = User::where('role','passenger')->count();
        $totalChoferes = User::where('role','driver')->count();
        $totalAdmins = User::where('role','admin')->count();
        $totalTarjetas = \App\Models\Card::count();
        $totalTransaccionesHoy = Transaction::whereDate('created_at', now()->toDateString())->count();

        return view('admin.dashboard', compact(
            'drivers','trips','passengers','transactions',
            'totalUsuarios','totalPasajeros','totalChoferes','totalAdmins','totalTarjetas','totalTransaccionesHoy',
            'tripsPage', 'tripsHasMore', 'tripsHasPrev', 'totalTrips',
            'transactionsPage', 'transactionsHasMore', 'transactionsHasPrev', 'totalTransactions'
        ));
    }

    /**
     * API endpoint para obtener viajes del dashboard via AJAX
     */
    public function getTripsData(Request $request)
    {
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));
        $driverId = $request->query('driver_id');
        $date = $request->query('date');

        $tripsQuery = Trip::with(['ruta','bus','driver'])
            ->withSum('transactions as transactions_sum_amount', 'amount');

        if ($driverId) {
            $tripsQuery->where('driver_id', $driverId);
        }

        if ($date) {
            $tripsQuery->whereDate('fecha', $date);
        }

        $totalTrips = $tripsQuery->count();
        $hasMore = $totalTrips > ($page * $perPage);
        $hasPrev = $page > 1;

        $trips = $tripsQuery->orderBy('fecha','desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $html = view('admin.partials.trips-table-rows', compact('trips'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $page,
            'hasMore' => $hasMore,
            'hasPrev' => $hasPrev,
            'total' => $totalTrips,
            'showing_from' => ($page - 1) * $perPage + 1,
            'showing_to' => min($page * $perPage, $totalTrips)
        ]);
    }

    /**
     * API endpoint para obtener transacciones del dashboard via AJAX
     */
    public function getTransactionsData(Request $request)
    {
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));
        $passengerId = $request->query('passenger_id');
        $transactionDate = $request->query('transaction_date');

        $transactionsQuery = Transaction::with(['card.passenger', 'ruta', 'bus', 'driver']);

        if ($passengerId) {
            $transactionsQuery->whereHas('card', function($q) use ($passengerId) {
                $q->where('passenger_id', $passengerId);
            });
        }

        if ($transactionDate) {
            $transactionsQuery->whereDate('created_at', $transactionDate);
        }

        $totalTransactions = $transactionsQuery->count();
        $hasMore = $totalTransactions > ($page * $perPage);
        $hasPrev = $page > 1;

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $html = view('admin.partials.transactions-table-rows', compact('transactions'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $page,
            'hasMore' => $hasMore,
            'hasPrev' => $hasPrev,
            'total' => $totalTransactions,
            'showing_from' => ($page - 1) * $perPage + 1,
            'showing_to' => min($page * $perPage, $totalTransactions)
        ]);
    }
}
