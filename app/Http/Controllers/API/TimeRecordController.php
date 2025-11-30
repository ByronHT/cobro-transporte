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
    public function registerTripStart(Trip $trip)
    {
        $driverId = $trip->driver_id;
        $today = Carbon::today();
        $tipoViaje = $trip->tipo_viaje; // 'ida' o 'vuelta'

        if ($tipoViaje === 'ida') {
            // Para viajes de IDA, siempre creamos un nuevo registro. Es más simple y robusto.
            $record = TimeRecord::create([
                'driver_id' => $driverId,
                'turno_id' => optional($this->getCurrentTurno($driverId))->id,
                'trip_ida_id' => $trip->id,
                'inicio_ida' => now(),
                'estado' => 'en_curso',
            ]);
            return $record;
        } 
        
        if ($tipoViaje === 'vuelta') {
            // Para viajes de VUELTA, buscamos el último registro del día que haya finalizado una ida y no tenga una vuelta iniciada.
            $recordToUpdate = TimeRecord::where('driver_id', $driverId)
                ->whereDate('created_at', $today)
                ->whereNotNull('fin_ida')
                ->whereNull('inicio_vuelta')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recordToUpdate) {
                $recordToUpdate->update([
                    'trip_vuelta_id' => $trip->id,
                    'inicio_vuelta' => now(),
                    'estado' => 'en_curso',
                    'fin_vuelta_estimado' => $this->getEstimatedEndTime($trip, $recordToUpdate),
                ]);
                return $recordToUpdate;
            } else {
                // Flujo inesperado: se inicia una vuelta sin una ida previa.
                // Creamos un registro solo con la info de vuelta para no perder el dato.
                $record = TimeRecord::create([
                    'driver_id' => $driverId,
                    'turno_id' => optional($this->getCurrentTurno($driverId))->id,
                    'trip_vuelta_id' => $trip->id,
                    'inicio_vuelta' => now(),
                    'estado' => 'en_curso',
                    'fin_vuelta_estimado' => $this->getEstimatedEndTime($trip, null),
                ]);
                return $record;
            }
        }

        return null; // No debería llegar aquí si tipo_viaje es siempre 'ida' o 'vuelta'
    }

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

    public function registerTripEnd(Trip $trip)
    {
        $driverId = $trip->driver_id;
        $today = Carbon::today();
        $tipoViaje = $trip->tipo_viaje;

        // Buscar el registro de horas que corresponda a este viaje.
        $record = TimeRecord::where('driver_id', $driverId)
            ->where(function ($query) use ($trip) {
                $query->where('trip_ida_id', $trip->id)
                      ->orWhere('trip_vuelta_id', $trip->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record) {
            // No se encontró un registro de tiempo para este viaje. No podemos continuar.
            return null;
        }

        $finReal = now();
        $updateData = [];

        if ($tipoViaje === 'ida' && $record->trip_ida_id === $trip->id && !$record->fin_ida) {
            $updateData['fin_ida'] = $finReal;
            
            // Solo calcular si la fecha de inicio existe, para evitar errores.
            if ($record->inicio_ida) {
                $tiempoReal = Carbon::parse($record->inicio_ida)->diffInMinutes($finReal);
                $tiempoEstimado = 45; // Default
                $retraso = $tiempoReal - $tiempoEstimado;
                
                if ($retraso > 5) {
                    $updateData['estado'] = 'retrasado';
                    $updateData['tiempo_retraso_minutos'] = $retraso;
                } else {
                    $updateData['estado'] = 'normal';
                }
            }
        } elseif ($tipoViaje === 'vuelta' && $record->trip_vuelta_id === $trip->id && !$record->fin_vuelta_real) {
            $updateData['fin_vuelta_real'] = $finReal;

            // Solo calcular retraso si la fecha estimada existe, para evitar errores.
            if ($record->fin_vuelta_estimado) {
                $llegadaEstimada = Carbon::parse($record->fin_vuelta_estimado);
                $retraso = $finReal->diffInMinutes($llegadaEstimada, false); // negativo si llegó antes
                
                if ($retraso > 5) {
                    $updateData['estado'] = 'retrasado';
                    $updateData['tiempo_retraso_minutos'] = $retraso;
                } else {
                    $updateData['estado'] = 'normal';
                }
            } else {
                $updateData['estado'] = 'normal'; // No se puede calcular retraso
            }

            // Marcar como último viaje si es después de las 9 PM
            if ($finReal->hour >= 21) {
                $updateData['es_ultimo_viaje'] = true;
            }
        }

        if (!empty($updateData)) {
            $record->update($updateData);
        }

        return $record;
    }

    /**
     * Calcula el tiempo estimado de fin para el viaje actual,
     * basado en la duración del viaje anterior en la misma ruta.
     *
     * @param Trip $currentTrip
     * @param TimeRecord|null $previousRecord El registro anterior para obtener la referencia de tiempo.
     * @return Carbon
     */
    private function getEstimatedEndTime(Trip $currentTrip, ?TimeRecord $previousRecord)
    {
        // Por defecto, si no hay registro previo o no se puede estimar, 45 minutos.
        $estimatedDurationMinutes = 45;

        // Intentar obtener la duración de viajes anteriores para la misma ruta
        $lastCompletedTripOnRoute = Trip::where('driver_id', $currentTrip->driver_id)
            ->where('ruta_id', $currentTrip->ruta_id)
            ->whereNotNull('fin')
            ->whereNotNull('inicio')
            ->where('id', '!=', $currentTrip->id) // Excluir el viaje actual
            ->orderBy('fin', 'desc')
            ->first();

        if ($lastCompletedTripOnRoute) {
            $duration = Carbon::parse($lastCompletedTripOnRoute->inicio)->diffInMinutes($lastCompletedTripOnRoute->fin);
            if ($duration > 0) {
                $estimatedDurationMinutes = $duration;
            }
        }

        return Carbon::now()->addMinutes($estimatedDurationMinutes);
    }

    /**
     * Obtiene el turno activo actual para un chofer.
     *
     * @param int $driverId
     * @return \App\Models\Turno|null
     */
    private function getCurrentTurno(int $driverId)
    {
        // Asumiendo que hay un modelo Turno y que tiene un estado 'activo' o similar
        // y que está asociado a un chofer_id
        return \App\Models\Turno::where('driver_id', $driverId)
            ->where('estado', 'activo') // O el estado que indique que está activo
            ->whereDate('fecha', Carbon::today())
            ->first();
    }

    /**
     * Registra inicio de viaje IDA
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
            ->with(['tripIda.bus', 'tripIda.ruta', 'tripVuelta.bus', 'tripVuelta.ruta'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($records);
    }
}
