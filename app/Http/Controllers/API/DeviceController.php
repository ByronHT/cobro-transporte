<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\BusCommand;

class DeviceController extends Controller
{
    /**
     * El dispositivo ESP8266 llama a este método periódicamente
     * para ver si hay comandos pendientes para él.
     */
    public function getCommand(Bus $bus)
    {
        // Buscar el comando más antiguo pendiente para este bus
        $pendingCommand = BusCommand::where('bus_id', $bus->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($pendingCommand) {
            // Marcar como en proceso
            $pendingCommand->update(['status' => 'processing']);

            $response = [
                'command' => $pendingCommand->command,
                'command_id' => $pendingCommand->id
            ];

            // Si el comando es 'start_trip', incluir el trip_id del viaje activo
            if ($pendingCommand->command === 'start_trip') {
                $activeTrip = \App\Models\Trip::where('bus_id', $bus->id)
                    ->whereNull('fin')
                    ->first();

                if ($activeTrip) {
                    $response['trip_id'] = $activeTrip->id;
                    $response['driver_id'] = $activeTrip->driver_id;
                }
            }

            return response()->json($response);
        }

        // Si no hay comandos pendientes
        return response()->json(['command' => 'none']);
    }

    /**
     * El dispositivo llama a este método cuando completa exitosamente un comando
     */
    public function markCommandAsCompleted($commandId)
    {
        $command = BusCommand::find($commandId);

        if (!$command) {
            return response()->json(['message' => 'Comando no encontrado'], 404);
        }

        $command->update([
            'status' => 'completed',
            'executed_at' => now()
        ]);

        return response()->json(['message' => 'Comando marcado como completado']);
    }

    /**
     * El dispositivo llama a este método cuando falla un comando
     */
    public function markCommandAsFailed(Request $request, $commandId)
    {
        $command = BusCommand::find($commandId);

        if (!$command) {
            return response()->json(['message' => 'Comando no encontrado'], 404);
        }

        $command->update([
            'status' => 'failed',
            'error_message' => $request->input('error_message', 'Error desconocido'),
            'executed_at' => now()
        ]);

        return response()->json(['message' => 'Comando marcado como fallido']);
    }
}
