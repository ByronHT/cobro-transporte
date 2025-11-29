<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TimeRecord;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimeRecordController extends Controller
{
    /**
     * Obtener registros de horas del chofer actual
     */
    public function getRecords(Request $request)
    {
        $driver = Auth::user();

        $records = TimeRecord::where('driver_id', $driver->id)
            ->with(['tripIda', 'tripVuelta'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($records);
    }

    /**
     * Registrar inicio de viaje IDA
     */
    public function startTripIda(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'turno_id' => 'nullable|exists:turnos,id',
        ]);

        $driver = Auth::user();
        $trip = Trip::findOrFail($request->trip_id);

        // Crear nuevo registro
        $record = TimeRecord::create([
            'driver_id' => $driver->id,
            'turno_id' => $request->turno_id,
            'trip_ida_id' => $trip->id,
            'inicio_ida' => now(),
            'estado' => 'en_curso',
        ]);

        return response()->json([
            'message' => 'Viaje IDA iniciado',
            'record' => $record
        ], 201);
    }

    /**
     * Registrar fin de viaje IDA
     */
    public function endTripIda(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:driver_time_records,id',
            'tiempo_estimado_minutos' => 'nullable|integer|min:1',
        ]);

        $record = TimeRecord::findOrFail($request->record_id);
        $driver = Auth::user();

        // Verificar que pertenece al chofer
        if ($record->driver_id !== $driver->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Calcular tiempo real
        $finIda = now();
        $tiempoReal = Carbon::parse($record->inicio_ida)->diffInMinutes($finIda);

        // Calcular retraso si se proporcionó tiempo estimado
        $tiempoEstimado = $request->tiempo_estimado_minutos ?? 45; // Default 45 min
        $retraso = $tiempoReal - $tiempoEstimado;

        // Actualizar registro
        $record->update([
            'fin_ida' => $finIda,
            'estado' => $retraso > 5 ? 'retrasado' : 'normal', // tolerancia 5 min
            'tiempo_retraso_minutos' => $retraso > 5 ? $retraso : null,
        ]);

        return response()->json([
            'message' => 'Viaje IDA finalizado',
            'record' => $record,
            'tiempo_real_minutos' => $tiempoReal,
            'retraso_minutos' => $retraso > 5 ? $retraso : 0,
        ]);
    }

    /**
     * Registrar inicio de viaje VUELTA
     */
    public function startTripVuelta(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'tiempo_estimado_minutos' => 'nullable|integer|min:1',
        ]);

        $driver = Auth::user();
        $trip = Trip::findOrFail($request->trip_id);

        // Calcular hora estimada de llegada
        $tiempoEstimado = $request->tiempo_estimado_minutos ?? 45; // Default 45 min
        $finEstimado = Carbon::now()->addMinutes($tiempoEstimado);

        // Crear nuevo registro
        $record = TimeRecord::create([
            'driver_id' => $driver->id,
            'turno_id' => $request->turno_id,
            'trip_vuelta_id' => $trip->id,
            'inicio_vuelta' => now(),
            'fin_vuelta_estimado' => $finEstimado,
            'estado' => 'en_curso',
        ]);

        return response()->json([
            'message' => 'Viaje VUELTA iniciado',
            'record' => $record
        ], 201);
    }

    /**
     * Registrar fin de viaje VUELTA
     */
    public function endTripVuelta(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:driver_time_records,id',
            'es_ultimo_viaje' => 'nullable|boolean',
        ]);

        $record = TimeRecord::findOrFail($request->record_id);
        $driver = Auth::user();

        // Verificar que pertenece al chofer
        if ($record->driver_id !== $driver->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $finReal = now();

        // Calcular si llegó tarde
        $llegadaEstimada = Carbon::parse($record->fin_vuelta_estimado);
        $retraso = $finReal->diffInMinutes($llegadaEstimada, false); // negativo si llegó antes

        // Actualizar registro
        $record->update([
            'fin_vuelta_real' => $finReal,
            'estado' => $retraso > 5 ? 'retrasado' : 'normal',
            'tiempo_retraso_minutos' => $retraso > 5 ? $retraso : null,
            'es_ultimo_viaje' => $request->es_ultimo_viaje ?? false,
        ]);

        return response()->json([
            'message' => 'Viaje VUELTA finalizado',
            'record' => $record,
            'retraso_minutos' => $retraso > 5 ? $retraso : 0,
        ]);
    }

    /**
     * Limpiar registros del día (cuando marca último viaje)
     */
    public function clearTodayRecords(Request $request)
    {
        $driver = Auth::user();

        // Marcar todos los registros de hoy como completados
        TimeRecord::where('driver_id', $driver->id)
            ->whereDate('created_at', today())
            ->update(['es_ultimo_viaje' => true]);

        return response()->json([
            'message' => 'Registros del día finalizados'
        ]);
    }

    /**
     * Obtener registros del turno actual
     */
    public function getTurnoRecords(Request $request)
    {
        $driver = Auth::user();

        $records = TimeRecord::where('driver_id', $driver->id)
            ->whereDate('created_at', today())
            ->where('es_ultimo_viaje', false)
            ->with(['tripIda', 'tripVuelta'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($records);
    }
}
