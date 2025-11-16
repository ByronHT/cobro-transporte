<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /**
     * Mostrar listado de reportes de choferes (solo viajes con reporte)
     * Excluye mensajes por defecto y reportes automáticos de devoluciones
     */
    public function index()
    {
        $reportes = Trip::with(['bus', 'ruta', 'driver'])
            ->whereNotNull('reporte')
            ->where('reporte', '!=', '')
            ->where('reporte', '!=', 'Viaje concluido sin novedades')
            ->where('reporte', 'NOT LIKE', '[DEVOLUCIÓN PROCESADA%')
            ->whereNotNull('fin')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.reportes.index', compact('reportes'));
    }

    /**
     * Mostrar detalle de un reporte específico
     */
    public function show($id)
    {
        $reporte = Trip::with(['bus', 'ruta', 'driver', 'transactions.card.passenger'])
            ->findOrFail($id);

        return view('admin.reportes.show', compact('reporte'));
    }

    /**
     * Marcar reporte como atendido
     */
    public function marcarAtendido($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update(['status' => 'atendido']);

        return redirect()->route('admin.reportes.index')
            ->with('success', 'Reporte marcado como atendido correctamente.');
    }

    /**
     * Cambiar estado del reporte
     */
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pendiente,atendido'
        ]);

        $trip = Trip::findOrFail($id);
        $trip->update(['status' => $request->status]);

        return redirect()->back()
            ->with('success', 'Estado actualizado correctamente.');
    }
}
