<x-app-layout>
    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden" style="height: calc(100vh - 180px);">
                <div class="flex h-full">
                    {{-- Mapa --}}
                    <div class="flex-1 relative">
                        <div id="map" style="width: 100%; height: 100%;"></div>

                        {{-- Filtro de líneas (flotante sobre el mapa) --}}
                        <div class="absolute top-4 left-4 bg-white rounded-lg shadow-lg p-4 z-10" style="min-width: 250px;">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Filtrar por Línea</label>
                            <select id="rutaFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Todas las líneas</option>
                                @foreach($rutas as $ruta)
                                    <option value="{{ $ruta->id }}">{{ $ruta->nombre }} - {{ $ruta->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Contador de buses (flotante) --}}
                        <div class="absolute top-4 right-4 bg-blue-600 text-white rounded-lg shadow-lg px-4 py-2 z-10">
                            <div class="text-sm font-semibold">Buses Activos</div>
                            <div class="text-2xl font-bold" id="busCount">0</div>
                        </div>
                    </div>

                    {{-- Sidebar con información del bus seleccionado --}}
                    <div class="w-96 bg-gray-50 border-l border-gray-200 overflow-y-auto">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Información del Bus
                            </h3>

                            <div id="busInfo">
                                <div class="text-center py-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-gray-600">Selecciona un bus en el mapa para ver su información</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @push('scripts')
    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map;
        let markers = {};
        let selectedBus = null;

        // Centro de Santa Cruz, Bolivia (ajusta según tu ciudad)
        const defaultCenter = [-17.7833, -63.1821];

        // Inicializar mapa con Leaflet + OpenStreetMap
        function initMap() {
            // Crear mapa
            map = L.map('map').setView(defaultCenter, 13);

            // Agregar capa de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Cargar buses activos
            loadActiveBuses();

            // Auto-refresh cada 10 segundos
            setInterval(loadActiveBuses, 10000);
        }

        // Cargar buses activos desde el backend
        function loadActiveBuses() {
            const rutaId = document.getElementById('rutaFilter').value;
            const url = `/admin/realtime/active-buses${rutaId ? '?ruta_id=' + rutaId : ''}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateBusMarkers(data.buses);
                        document.getElementById('busCount').textContent = data.count;

                        // Si no hay buses, mostrar mensaje
                        if (data.count === 0) {
                            showNoBusesMessage();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading buses:', error);
                    showErrorMessage();
                });
        }

        // Icono personalizado para buses
        const busIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3b82f6" width="32" height="32">
                    <path d="M12 2C7 2 3 6 3 11c0 5.25 9 13 9 13s9-7.75 9-13c0-5-4-9-9-9zm0 12.5c-1.93 0-3.5-1.57-3.5-3.5S10.07 7.5 12 7.5s3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                </svg>
            `),
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const busIconSelected = L.icon({
            iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" width="40" height="40">
                    <path d="M12 2C7 2 3 6 3 11c0 5.25 9 13 9 13s9-7.75 9-13c0-5-4-9-9-9zm0 12.5c-1.93 0-3.5-1.57-3.5-3.5S10.07 7.5 12 7.5s3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
                </svg>
            `),
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // Actualizar marcadores en el mapa
        function updateBusMarkers(buses) {
            // Limpiar marcadores antiguos que ya no existen
            Object.keys(markers).forEach(busId => {
                if (!buses.find(b => b.bus_id == busId)) {
                    map.removeLayer(markers[busId]);
                    delete markers[busId];
                }
            });

            // Agregar o actualizar marcadores
            buses.forEach(bus => {
                const position = [bus.latitude, bus.longitude];
                const isSelected = selectedBus && selectedBus.bus_id === bus.bus_id;

                if (markers[bus.bus_id]) {
                    // Actualizar posición del marcador existente
                    markers[bus.bus_id].setLatLng(position);
                    markers[bus.bus_id].setIcon(isSelected ? busIconSelected : busIcon);
                } else {
                    // Crear nuevo marcador
                    const marker = L.marker(position, {
                        icon: isSelected ? busIconSelected : busIcon
                    }).addTo(map);

                    marker.bindPopup(`
                        <strong>${bus.bus_plate}</strong><br>
                        ${bus.ruta_nombre}<br>
                        Chofer: ${bus.driver_name}
                    `);

                    // Click en marcador para mostrar info
                    marker.on('click', () => {
                        selectBus(bus);
                    });

                    markers[bus.bus_id] = marker;
                }
            });

            // Auto-ajustar zoom si hay buses
            if (buses.length > 0) {
                const bounds = L.latLngBounds(buses.map(b => [b.latitude, b.longitude]));
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        }

        // Mostrar información del bus seleccionado
        function selectBus(bus) {
            selectedBus = bus;

            // Actualizar iconos
            Object.keys(markers).forEach(busId => {
                const isSelected = busId == bus.bus_id;
                markers[busId].setIcon(isSelected ? busIconSelected : busIcon);
            });

            const html = `
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-lg -mx-6 -mt-6 mb-4">
                        <div class="text-sm font-semibold mb-1">${bus.ruta_nombre}</div>
                        <div class="text-2xl font-bold">${bus.bus_plate}</div>
                        ${bus.ruta_descripcion ? `<div class="text-xs opacity-90">${bus.ruta_descripcion}</div>` : ''}
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">Chofer</div>
                        <div class="text-lg font-semibold text-gray-800">${bus.driver_name}</div>
                        <div class="text-xs text-gray-500 mt-1">ID: ${bus.driver_id}</div>
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">Estado del Viaje</div>
                        <div class="text-base font-bold ${bus.trip_status === 'activo' ? 'text-green-600' : 'text-gray-600'}">
                            ${bus.trip_status === 'activo' ? '✅ Activo' : '⏸️ Finalizado'}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">ID Viaje: ${bus.trip_id || 'N/A'}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                            <div class="text-xs text-gray-500 mb-1">⏰ Hora de Salida</div>
                            <div class="text-base font-bold text-gray-800">${bus.trip_start_formatted || '-'}</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm border ${bus.trip_end_formatted === 'En curso' ? 'border-yellow-300 bg-yellow-50' : 'border-green-300 bg-green-50'}">
                            <div class="text-xs text-gray-500 mb-1">🔄 Conclusión del Viaje</div>
                            <div class="text-base font-bold ${bus.trip_end_formatted === 'En curso' ? 'text-yellow-600' : 'text-green-600'}">
                                ${bus.trip_end_formatted}
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                        <div class="text-xs text-green-700 font-semibold mb-1">Monto Acumulado del Viaje</div>
                        <div class="text-3xl font-bold text-green-700">Bs ${bus.trip_earnings}</div>
                    </div>

                    ${bus.speed ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                            <div class="text-xs text-gray-500 mb-1">Velocidad Actual</div>
                            <div class="text-lg font-bold text-gray-800">${bus.speed.toFixed(1)} km/h</div>
                        </div>
                    ` : ''}

                    ${bus.has_report ? `
                        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                            <div class="text-xs text-yellow-700 font-semibold mb-2">📝 Reporte del Chofer</div>
                            <div class="text-sm text-gray-700 whitespace-pre-wrap">${bus.trip_report || 'Sin reporte'}</div>
                        </div>
                    ` : `
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 text-center">
                            <div class="text-xs text-gray-500">Sin reporte disponible</div>
                        </div>
                    `}

                    <div class="text-xs text-gray-500 text-center pt-2 border-t">
                        Última actualización: ${bus.last_update}
                    </div>
                </div>
            `;

            document.getElementById('busInfo').innerHTML = html;
        }

        // Mostrar mensaje cuando no hay buses activos
        function showNoBusesMessage() {
            document.getElementById('busInfo').innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-600 font-semibold mb-2">No hay buses activos</p>
                    <p class="text-gray-500 text-sm">Los buses aparecerán aquí cuando estén en ruta</p>
                </div>
            `;
        }

        // Mostrar mensaje de error
        function showErrorMessage() {
            document.getElementById('busInfo').innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-600 font-semibold">Error al cargar datos</p>
                </div>
            `;
        }

        // Event listener para filtro
        document.getElementById('rutaFilter').addEventListener('change', loadActiveBuses);

        // Iniciar mapa cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
    @endpush
</x-app-layout>
