<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolucionController extends Controller
{
    /**
     * Mostrar listado de devoluciones aprobadas
     */
    public function index()
    {
        $devoluciones = RefundRequest::with(['transaction.card.passenger', 'transaction.trip.bus', 'transaction.trip.ruta', 'transaction.trip.driver'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.devoluciones.index', compact('devoluciones'));
    }

    /**
     * Mostrar formulario de edición de devolución
     */
    public function edit($id)
    {
        $devolucion = RefundRequest::with(['transaction.card.passenger', 'transaction.trip.bus', 'transaction.trip.ruta', 'transaction.trip.driver'])
            ->findOrFail($id);

        return view('admin.devoluciones.edit', compact('devolucion'));
    }

    /**
     * Revertir una devolución (eliminarla y restaurar saldos)
     */
    public function revertir(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $refundRequest = RefundRequest::with('transaction.card')->findOrFail($id);

            // Verificar que está completada
            if ($refundRequest->status !== 'completed') {
                return redirect()->back()->with('error', 'Solo se pueden revertir devoluciones completadas.');
            }

            $transaction = $refundRequest->transaction;
            $card = $transaction->card;

            // Revertir saldo de la tarjeta (quitar el monto devuelto)
            $card->balance -= abs($transaction->amount);
            $card->save();

            // Eliminar la transacción de devolución
            Transaction::where('type', 'refund')
                ->where('card_id', $card->id)
                ->where('trip_id', $transaction->trip_id)
                ->where('amount', $transaction->amount)
                ->delete();

            // Marcar solicitud como revertida
            $refundRequest->update(['status' => 'reverted']);

            DB::commit();

            return redirect()->route('admin.devoluciones.index')
                ->with('success', 'Devolución revertida correctamente. Saldo actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al revertir la devolución: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar información de la devolución
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $devolucion = RefundRequest::findOrFail($id);
        $devolucion->update([
            'reason' => $request->reason
        ]);

        return redirect()->route('admin.devoluciones.index')
            ->with('success', 'Devolución actualizada correctamente.');
    }
}
