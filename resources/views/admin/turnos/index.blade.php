<x-app-layout>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- Filtros --}}
        <div class="mb-6 p-6 bg-white rounded-xl shadow-lg">
            <h3 class="font-bold text-xl text-gray-800 mb-4">Filtrar Turnos</h3>
            <form method="GET" action="{{ route('admin.turnos.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Selector de Chofer --}}
                    <div>
                        <label for="driver_id" class="block text-sm font-medium text-gray-700">Chofer</label>
                        <select name="driver_id" id="driver_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos los choferes</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ $driverId == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Selector de Fecha --}}
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input type="date" name="date" id="date" value="{{ $date }}" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>

                    {{-- Botón de Filtrar --}}
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg shadow-lg hover:bg-blue-700 transition-all duration-200 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Tabla de turnos --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700">
                <h3 class="font-bold text-xl text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Listado de Turnos
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Chofer</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Bus Inicial</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Horas</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total Turno</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($turnos as $turno)
                            <tr class="hover:bg-blue-50 transition-colors duration-150" x-data="{ open: false }">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $turno->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">{{ $turno->driver->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $turno->busInicial->plate ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    Inicio: {{ \Carbon\Carbon::parse($turno->hora_inicio)->format('H:i') }} <br>
                                    Fin Prog.: {{ $turno->hora_fin_programada ? \Carbon\Carbon::parse($turno->hora_fin_programada)->format('H:i') : '-' }} <br>
                                    Fin Real: {{ $turno->hora_fin_real ? \Carbon\Carbon::parse($turno->hora_fin_real)->format('H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($turno->status === 'activo')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                    @elseif($turno->status === 'finalizado')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Finalizado</span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($turno->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Bs. {{ number_format($turno->total_recaudado, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="open = !open" class="text-blue-600 hover:text-blue-900">
                                        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="open" style="display: none;">
                                <td colspan="8" class="p-4 bg-gray-50">
                                    <div class="p-4 bg-white rounded-lg shadow-inner">
                                        <h4 class="font-bold text-md text-gray-700 mb-3">Detalle de Viajes del Turno #{{ $turno->id }}</h4>
                                        @if($turno->trips->isEmpty())
                                            <p class="text-sm text-gray-500">Este turno no tiene viajes registrados.</p>
                                        @else
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Tipo</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Ruta</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Bus</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Inicio</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Fin</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Recaudado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($turno->trips as $trip)
                                                        <tr class="border-b">
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                                @if($trip->tipo_viaje == 'ida')
                                                                    <span class="font-semibold text-blue-600">Ida</span>
                                                                @else
                                                                    <span class="font-semibold text-green-600">Vuelta</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $trip->ruta->nombre ?? 'N/A' }}</td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $trip->bus->plate ?? 'N/A' }}</td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($trip->inicio)->format('H:i:s') }}</td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $trip->fin ? \Carbon\Carbon::parse($trip->fin)->format('H:i:s') : 'En curso' }}</td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-800">Bs. {{ number_format($trip->total_recaudado, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No se encontraron turnos para la selección actual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $turnos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
