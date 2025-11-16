<x-app-layout>
    <x-slot name="header"><h2>Monitoreo de Viajes por Chofer</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6">

                <form method="GET" action="{{ route('admin.monitoring.trips') }}" class="mb-6">
                    <div class="flex items-center space-x-4">
                        <label for="driver_id" class="block text-sm font-bold text-gray-700">Seleccionar Chofer:</label>
                        <select name="driver_id" id="driver_id" class="form-select mt-1 block w-1/2 rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Todos los Choferes --</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ $selectedDriverId == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }} (ID: {{ $driver->id }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filtrar</button>
                    </div>
                </form>

                <h3 class="text-lg font-bold mb-4">Viajes Realizados @if($selectedDriverId) por {{ $drivers->firstWhere('id', $selectedDriverId)->name }} @endif</h3>

                @if($trips->isEmpty())
                    <p class="text-gray-600">No hay viajes registrados para la selección actual.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Viaje</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chofer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bus</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Línea</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarifa</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fin</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasajero (Tarjeta)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporte</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($trips as $trip)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->driver->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->bus->plate ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->bus->ruta->nombre ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($trip->fare, 2) }} Bs</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->inicio ? \Carbon\Carbon::parse($trip->inicio)->format('d M Y H:i') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->fin ? \Carbon\Carbon::parse($trip->fin)->format('d M Y H:i') : 'Activo' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $trip->card->uid ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($trip->reporte && $trip->reporte !== '' && $trip->reporte !== 'Viaje concluido sin novedades')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    ⚠️ Novedades
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    ✅ Sin novedades
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $trips->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
