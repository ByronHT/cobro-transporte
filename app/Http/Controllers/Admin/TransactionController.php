<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Card;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Paginación manual
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));

        // Filtros
        $passengerId = $request->query('passenger_id');
        $transactionDate = $request->query('transaction_date');

        // Datos para filtros UI
        $passengers = User::where('role', 'passenger')->where('active', 1)->get();

        // Query de transacciones
        $transactionsQuery = Transaction::with(['card.passenger', 'ruta', 'bus', 'driver']);

        if ($passengerId) {
            $transactionsQuery->whereHas('card', function($q) use ($passengerId) {
                $q->where('passenger_id', $passengerId);
            });
        }

        if ($transactionDate) {
            $transactionsQuery->whereDate('created_at', $transactionDate);
        }

        // Contar total y calcular paginación
        $totalTransactions = $transactionsQuery->count();
        $hasMore = $totalTransactions > ($page * $perPage);
        $hasPrev = $page > 1;

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return view('admin.transactions.index', compact('transactions', 'passengers', 'page', 'hasMore', 'hasPrev', 'totalTransactions'));
    }

    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);
        return view('admin.transactions.edit', compact('transaction'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255'
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->only(['amount', 'description']));

        return redirect()->route('admin.transactions.index')->with('success', 'Transacción actualizada correctamente.');
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);

        // Revertir el saldo de la tarjeta
        $card = $transaction->card;
        if ($transaction->type === 'fare') {
            // Si fue un cobro de pasaje, devolver el monto
            $card->balance += $transaction->amount;
        } else {
            // Si fue una recarga, restar el monto
            $card->balance -= $transaction->amount;
        }
        $card->save();

        $transaction->delete();

        return redirect()->route('admin.transactions.index')->with('success', 'Transacción eliminada correctamente y saldo revertido.');
    }

    /**
     * API endpoint para obtener transactions via AJAX
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

        $html = view('admin.partials.transactions-index-rows', compact('transactions'))->render();

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
