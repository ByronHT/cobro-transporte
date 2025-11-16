<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
    /**
     * Obtener todas las rutas disponibles
     */
    public function getRoutes(Request $request)
    {
        try {
            $routes = \App\Models\Ruta::select('id', 'nombre', 'descripcion')
                ->orderBy('nombre')
                ->get();

            return response()->json($routes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener rutas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener choferes que trabajan en una ruta específica
     */
    public function getDriversByRoute(Request $request, $routeId)
    {
        try {
            // Obtener todos los choferes activos que han trabajado en esta ruta
            // Buscamos tanto por buses asignados a la ruta como por viajes en esa ruta
            $driversFromTrips = User::where('role', 'driver')
                ->where('active', 1)
                ->whereHas('trips', function ($query) use ($routeId) {
                    $query->where('ruta_id', $routeId);
                })
                ->pluck('id');

            $driversFromBuses = \App\Models\Trip::where('ruta_id', $routeId)
                ->whereNotNull('driver_id')
                ->distinct()
                ->pluck('driver_id');

            // Combinar ambos conjuntos de IDs
            $driverIds = $driversFromTrips->merge($driversFromBuses)->unique();

            // Si no hay choferes, retornar todos los choferes activos como fallback
            if ($driverIds->isEmpty()) {
                $drivers = User::where('role', 'driver')
                    ->where('active', 1)
                    ->select('id', 'name', 'email')
                    ->orderBy('name')
                    ->get();
            } else {
                $drivers = User::whereIn('id', $driverIds)
                    ->where('active', 1)
                    ->select('id', 'name', 'email')
                    ->orderBy('name')
                    ->get();
            }

            return response()->json($drivers);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener choferes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los viajes del pasajero para reportar
     */
    public function getPassengerTransactions(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'passenger') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los viajes donde el pasajero tuvo transacciones
            $trips = \App\Models\Trip::with([
                'driver:id,name',
                'bus:id,placa',
                'ruta:id,descripcion',
                'transactions' => function ($query) use ($user) {
                    $query->whereHas('card', function ($q) use ($user) {
                        $q->where('passenger_id', $user->id);
                    });
                }
            ])
            ->whereHas('transactions.card', function ($query) use ($user) {
                $query->where('passenger_id', $user->id);
            })
            ->whereNotNull('fin') // Solo viajes finalizados
            ->orderBy('fecha', 'desc')
            ->orderBy('inicio', 'desc')
            ->get()
            ->map(function ($trip) {
                // Obtener la primera transacción del pasajero en este viaje
                $transaction = $trip->transactions->first();

                return [
                    'trip_id' => $trip->id,
                    'transaction_id' => $transaction->id ?? null,
                    'driver_name' => $trip->driver->name ?? 'N/A',
                    'driver_id' => $trip->driver_id,
                    'bus_placa' => $trip->bus->placa ?? 'N/A',
                    'bus_id' => $trip->bus_id,
                    'ruta_descripcion' => $trip->ruta->descripcion ?? 'N/A',
                    'ruta_id' => $trip->ruta_id,
                    'fecha' => $trip->fecha->format('Y-m-d'),
                    'inicio' => $trip->inicio ? $trip->inicio->format('H:i') : 'N/A',
                    'fin' => $trip->fin ? $trip->fin->format('H:i') : 'N/A',
                    'created_at' => $trip->inicio ? $trip->inicio->format('Y-m-d H:i:s') : $trip->fecha->format('Y-m-d'),
                ];
            });

            return response()->json($trips);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener viajes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva queja
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'passenger') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $validator = Validator::make($request->all(), [
                'driver_id' => 'required|exists:users,id',
                'ruta_id' => 'required|exists:rutas,id',
                'transaction_id' => 'nullable|exists:transactions,id',
                'trip_id' => 'nullable|exists:trips,id',
                'bus_id' => 'nullable|exists:buses,id',
                'reason' => 'required|string|min:10|max:1000',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Datos inválidos',
                    'details' => $validator->errors()
                ], 422);
            }

            // Verificar que la transacción pertenece al pasajero (si se proporciona)
            if ($request->transaction_id) {
                $transaction = Transaction::with('card')->find($request->transaction_id);
                if ($transaction && $transaction->card->passenger_id !== $user->id) {
                    return response()->json(['error' => 'Transacción no válida'], 403);
                }
            }

            // Verificar que el driver_id corresponde a un usuario con rol 'driver'
            $driver = User::find($request->driver_id);
            if (!$driver || $driver->role !== 'driver') {
                return response()->json(['error' => 'Chofer no válido'], 422);
            }

            // Manejar la subida de foto si existe
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('complaints', 'public');
            }

            // Crear la queja
            $complaint = Complaint::create([
                'passenger_id' => $user->id,
                'driver_id' => $request->driver_id,
                'transaction_id' => $request->transaction_id,
                'trip_id' => $request->trip_id,
                'bus_id' => $request->bus_id,
                'ruta_id' => $request->ruta_id,
                'reason' => $request->reason,
                'photo_path' => $photoPath,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Queja registrada exitosamente',
                'complaint' => $complaint
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar queja',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener las quejas del pasajero actual
     */
    public function getMyComplaints(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'passenger') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $complaints = Complaint::with([
                'driver:id,name',
                'trip.bus:id,placa',
                'trip.ruta:id,descripcion',
                'transaction'
            ])
            ->where('passenger_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'driver_name' => $complaint->driver->name ?? 'N/A',
                    'bus_placa' => $complaint->trip->bus->placa ?? 'N/A',
                    'ruta_descripcion' => $complaint->trip->ruta->descripcion ?? 'N/A',
                    'reason' => $complaint->reason,
                    'status' => $complaint->status,
                    'photo_path' => $complaint->photo_path ? asset('storage/' . $complaint->photo_path) : null,
                    'admin_response' => $complaint->admin_response,
                    'created_at' => $complaint->created_at->format('Y-m-d H:i:s'),
                    'reviewed_at' => $complaint->reviewed_at ? $complaint->reviewed_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json($complaints);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener quejas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las quejas (admin)
     */
    public function getAllComplaints(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $status = $request->query('status', 'all');

            $query = Complaint::with([
                'passenger:id,name,email',
                'driver:id,name,email',
                'trip.bus:id,placa',
                'trip.ruta:id,descripcion',
                'transaction',
                'reviewer:id,name'
            ]);

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $complaints = $query->orderBy('created_at', 'desc')->get();

            return response()->json($complaints);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener quejas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado de una queja (admin)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin') {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,reviewed',
                'admin_response' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Datos inválidos',
                    'details' => $validator->errors()
                ], 422);
            }

            $complaint = Complaint::find($id);

            if (!$complaint) {
                return response()->json(['error' => 'Queja no encontrada'], 404);
            }

            $complaint->update([
                'status' => $request->status,
                'admin_response' => $request->admin_response,
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Queja actualizada exitosamente',
                'complaint' => $complaint
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar queja',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
