<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TurnoController extends Controller
{
    public function startTurno(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_id' => 'required|exists:buses,id',
            'hora_fin_programada' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        $driver = $request->user();

        if ($driver->role !== 'driver') {
            return response()->json([
                'error' => 'Solo los choferes pueden iniciar turnos'
            ], 403);
        }

        $turnoActivo = Turno::where('driver_id', $driver->id)
                            ->where('status', 'activo')
                            ->first();

        if ($turnoActivo) {
            return response()->json([
                'error' => 'Ya tienes un turno activo',
                'turno' => $turnoActivo
            ], 400);
        }

        $turno = Turno::create([
            'driver_id' => $driver->id,
            'bus_inicial_id' => $request->bus_id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => now()->format('H:i'),
            'hora_fin_programada' => $request->hora_fin_programada,
            'status' => 'activo'
        ]);

        return response()->json([
            'message' => 'Turno iniciado exitosamente',
            'turno' => $turno->load('busInicial', 'driver')
        ], 201);
    }

    public function finishTurno(Request $request)
    {
        $driver = $request->user();

        $turno = Turno::where('driver_id', $driver->id)
                      ->where('status', 'activo')
                      ->first();

        if (!$turno) {
            return response()->json([
                'error' => 'No tienes un turno activo'
            ], 404);
        }

        $viajeActivo = $turno->trips()->whereNull('fin')->first();
        if ($viajeActivo) {
            return response()->json([
                'error' => 'Debes finalizar el viaje actual antes de terminar el turno',
                'viaje_activo' => $viajeActivo
            ], 400);
        }

        $turno->finalizar();

        return response()->json([
            'message' => 'Turno finalizado exitosamente',
            'turno' => $turno->load('busInicial', 'driver', 'trips')
        ]);
    }

    public function getActiveTurno(Request $request)
    {
        $driver = $request->user();

        $turno = Turno::where('driver_id', $driver->id)
                      ->where('status', 'activo')
                      ->with('busInicial', 'trips.bus', 'trips.ruta')
                      ->first();

        if (!$turno) {
            return response()->json([
                'message' => 'No tienes un turno activo',
                'turno' => null
            ]);
        }

        return response()->json([
            'turno' => $turno
        ]);
    }

    public function getHistorial(Request $request)
    {
        $driver = $request->user();

        $turnos = Turno::where('driver_id', $driver->id)
                       ->with('busInicial')
                       ->orderBy('fecha', 'desc')
                       ->orderBy('hora_inicio', 'desc')
                       ->paginate(20);

        return response()->json($turnos);
    }

    public function getBusesDisponibles(Request $request)
    {
        $busesOcupados = DB::table('trips')
                          ->whereNull('fin')
                          ->pluck('bus_id');

        $buses = Bus::whereNotIn('id', $busesOcupados)
                    ->with('ruta')
                    ->get();

        return response()->json([
            'buses' => $buses
        ]);
    }
}
