<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4">
        {{-- Botones de acción --}}
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('admin.trips.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl shadow-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Crear Viaje
            </a>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 text-white rounded-xl shadow-lg hover:bg-gray-700 transition-all duration-200 font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Panel
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <p class="text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                </svg>
                Filtros de Búsqueda
            </h3>
            <form method="GET" action="{{ route('admin.trips.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-full sm:w-1/4">
                    <label class="block text-sm font-medium text-gray-700">Chofer</label>
                    <select name="driver_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-1/4">
                    <label class="block text-sm font-medium text-gray-700">Bus</label>
                    <select name="bus_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach($buses as $bus)
                            <option value="{{ $bus->id }}" {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
                                {{ $bus->plate }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-1/4">
                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('admin.trips.index') }}" class="px-6 py-2.5 bg-gray-500 text-white rounded-lg shadow-md hover:bg-gray-600 transition-all duration-200 font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <div id="trips-table" class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700">
                <h3 class="font-bold text-xl text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                    </svg>
                    Listado de Viajes
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Bus</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Chofer</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Línea</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Inicio / Fin</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Monto Acumulado</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($trips as $trip)
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $trip->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-900">{{ $trip->bus?->plate ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ $trip->driver?->name ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {{ $trip->ruta?->nombre ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($trip->tipo_viaje == 'ida')
                                        <span class="font-semibold text-blue-600">Ida</span>
                                    @elseif($trip->tipo_viaje == 'vuelta')
                                        <span class="font-semibold text-green-600">Vuelta</span>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                    <div>{{ $trip->inicio ? \Carbon\Carbon::parse($trip->inicio)->format('d/m/Y H:i') : '-' }}</div>
                                    <div class="font-semibold">{{ $trip->fin ? \Carbon\Carbon::parse($trip->fin)->format('H:i') : 'En curso' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900">Bs {{ number_format($trip->transactions_sum_amount ?? 0, 2) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.trips.edit', $trip->id) }}"
                                           class="inline-flex items-center gap-1 p-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.trips.destroy', $trip->id) }}"
                                              onsubmit="return confirm('¿Eliminar este viaje?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            {{-- Paginación Manual --}}
            @if($hasMore || $hasPrev)
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div id="trips-counter" class="text-sm text-gray-600">
                        Mostrando {{ ($page - 1) * 8 + 1 }} - {{ min($page * 8, $totalTrips) }} de {{ $totalTrips }} viajes
                    </div>
                    <div id="trips-pagination" class="flex gap-2">
                        @if($hasPrev)
                            <button onclick="loadTripsPage({{ $page - 1 }})" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Anterior
                            </button>
                        @endif

                        @if($hasMore)
                            <button onclick="loadTripsPage({{ $page + 1 }})" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                Siguiente
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let currentPage = {{ $page }};

                // Función para cargar viajes vía AJAX
                function loadTrips(page) {
                    const tbody = document.querySelector('#trips-table tbody');
                    const pagination = document.querySelector('#trips-pagination');
                    const counter = document.querySelector('#trips-counter');

                    // Mostrar indicador de carga
                    tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-sm text-gray-500">Cargando...</p></td></tr>';

                    // Obtener filtros actuales
                    const driverId = new URLSearchParams(window.location.search).get('driver_id') || '';
                    const busId = new URLSearchParams(window.location.search).get('bus_id') || '';
                    const date = new URLSearchParams(window.location.search).get('date') || '';

                    // Hacer petición AJAX
                    fetch(`{{ route('admin.ajax.trips') }}?page=${page}&driver_id=${driverId}&bus_id=${busId}&date=${date}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                tbody.innerHTML = data.html;
                                currentPage = data.page;
                                updatePagination(data.page, data.hasPrev, data.hasMore);

                                if (counter) {
                                    counter.textContent = `Mostrando ${data.showing_from} - ${data.showing_to} de ${data.total} viajes`;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center text-red-600">Error al cargar los datos</td></tr>';
                        });
                }

                // Función para actualizar botones de paginación
                function updatePagination(page, hasPrev, hasMore) {
                    const pagination = document.querySelector('#trips-pagination');
                    if (!pagination) return;

                    let html = '';

                    if (hasPrev) {
                        html += `
                            <button onclick="loadTripsPage(${page - 1})" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Anterior
                            </button>
                        `;
                    }

                    if (hasMore) {
                        html += `
                            <button onclick="loadTripsPage(${page + 1})" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150 font-semibold">
                                Siguiente
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        `;
                    }

                    pagination.innerHTML = html;
                }

                // Exponer función globalmente
                window.loadTripsPage = loadTrips;
            });

            // Función para expandir/colapsar texto de reportes (expansión vertical)
            window.toggleReportText = function(button) {
                const textDiv = button.previousElementSibling;
                const isExpanded = textDiv.classList.contains('expanded');

                if (isExpanded) {
                    textDiv.classList.remove('expanded', 'max-h-96');
                    textDiv.classList.add('max-h-12', 'overflow-hidden');
                    button.textContent = 'Ver más ▼';
                } else {
                    textDiv.classList.add('expanded', 'max-h-96');
                    textDiv.classList.remove('max-h-12', 'overflow-hidden');
                    button.textContent = 'Ver menos ▲';
                }
            };
        </script>
    @endpush
</x-app-layout>
