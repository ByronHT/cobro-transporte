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

    @push('scripts')
    <script>
        let map;
        let markers = {};
        let selectedBus = null;
        let hasGoogleMaps = false;

        // Verificar si Google Maps está disponible
        function checkGoogleMaps() {
            return typeof google !== 'undefined' && typeof google.maps !== 'undefined';
        }

        // Inicializar mapa (requiere Google Maps API Key)
        function initMap() {
            hasGoogleMaps = checkGoogleMaps();

            if (!hasGoogleMaps) {
                console.warn('Google Maps API no disponible. Mostrando mensaje al usuario.');
                showNoMapMessage();
                // Aún así, cargar buses para mostrar en el sidebar
                loadActiveBuses();
                setInterval(loadActiveBuses, 10000);
                return;
            }

            // Centro de Santa Cruz, Bolivia (ajusta según tu ciudad)
            const center = { lat: -17.7833, lng: -63.1821 };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: center,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
                styles: []
            });

            // Cargar buses activos
            loadActiveBuses();

            // Auto-refresh cada 10 segundos
            setInterval(loadActiveBuses, 10000);
        }

        // Mostrar mensaje cuando no hay API Key
        function showNoMapMessage() {
            document.getElementById('map').innerHTML = `
                <div class="flex items-center justify-center h-full bg-gradient-to-br from-blue-50 to-blue-100">
                    <div class="text-center p-8 bg-white rounded-xl shadow-lg max-w-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-20 w-20 text-yellow-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Google Maps API Key Requerida</h3>
                        <p class="text-gray-600 mb-4">Para visualizar el mapa en tiempo real, necesitas configurar una API Key de Google Maps.</p>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 text-left text-sm mb-4">
                            <p class="font-semibold text-blue-800 mb-2">📋 Pasos para configurar:</p>
                            <ol class="list-decimal list-inside text-blue-700 space-y-2">
                                <li>Ir a <a href="https://console.cloud.google.com" target="_blank" class="underline hover:text-blue-900">Google Cloud Console</a></li>
                                <li>Crear proyecto y habilitar "Maps JavaScript API"</li>
                                <li>Crear API Key con restricciones de dominio</li>
                                <li>Agregar la API Key al archivo <code class="bg-blue-100 px-1 rounded">.env</code>:</li>
                            </ol>
                        </div>
                        <div class="bg-gray-800 text-green-400 p-3 rounded-lg text-sm font-mono mb-4">
                            <div class="text-gray-400"># Agregar al final del archivo .env</div>
                            <div>GOOGLE_MAPS_API_KEY=tu_api_key_aqui</div>
                        </div>
                        <div class="flex items-start gap-2 bg-yellow-50 border border-yellow-200 rounded p-3 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-yellow-800">
                                <strong>Cuota gratuita:</strong> Google Maps ofrece $200 USD/mes gratis (~28,000 cargas de mapa)
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4 text-center">💡 Mientras tanto, puedes ver la información de los buses en el panel lateral →</p>
                    </div>
                </div>
            `;
        }

        // Cargar buses activos desde el backend
        function loadActiveBuses() {
            const rutaId = document.getElementById('rutaFilter').value;
            const url = `/admin/realtime/active-buses${rutaId ? '?ruta_id=' + rutaId : ''}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (hasGoogleMaps) {
                            updateBusMarkers(data.buses);
                        } else {
                            showBusesList(data.buses);
                        }
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

        // Mostrar lista de buses cuando no hay mapa
        function showBusesList(buses) {
            if (buses.length === 0) return;

            const html = `
                <div class="space-y-3">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Buses Activos</h3>
                    ${buses.map(bus => `
                        <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-blue-500 hover:shadow-md transition-shadow cursor-pointer"
                             onclick='selectBus(${JSON.stringify(bus)})'>
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-lg font-bold text-gray-800">${bus.bus_plate}</div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    ${bus.ruta_nombre}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <div>👨‍✈️ ${bus.driver_name}</div>
                                <div>💰 Bs ${bus.trip_earnings}</div>
                                ${bus.speed ? `<div>🚀 ${bus.speed.toFixed(1)} km/h</div>` : ''}
                            </div>
                        </div>
                    `).join('')}
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

        // Actualizar marcadores en el mapa
        function updateBusMarkers(buses) {
            // Limpiar marcadores antiguos que ya no existen
            Object.keys(markers).forEach(busId => {
                if (!buses.find(b => b.bus_id == busId)) {
                    markers[busId].setMap(null);
                    delete markers[busId];
                }
            });

            // Agregar o actualizar marcadores
            buses.forEach(bus => {
                const position = { lat: bus.latitude, lng: bus.longitude };

                if (markers[bus.bus_id]) {
                    // Actualizar posición del marcador existente
                    markers[bus.bus_id].setPosition(position);
                } else {
                    // Crear nuevo marcador
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: `${bus.ruta_nombre} - ${bus.bus_plate}`,
                        icon: {
                            url: 'http://maps.google.com/mapfiles/ms/icons/bus.png'
                        },
                        animation: google.maps.Animation.DROP
                    });

                    // Click en marcador para mostrar info
                    marker.addListener('click', () => {
                        selectBus(bus);
                    });

                    markers[bus.bus_id] = marker;
                }
            });
        }

        // Mostrar información del bus seleccionado
        function selectBus(bus) {
            selectedBus = bus;

            const html = `
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-lg -mx-6 -mt-6 mb-4">
                        <div class="text-sm font-semibold mb-1">${bus.ruta_nombre}</div>
                        <div class="text-2xl font-bold">${bus.bus_plate}</div>
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">Chofer</div>
                        <div class="text-lg font-semibold text-gray-800">${bus.driver_name}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                            <div class="text-xs text-gray-500 mb-1">Hora Salida</div>
                            <div class="text-base font-bold text-gray-800">${bus.trip_start_formatted || '-'}</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                            <div class="text-xs text-gray-500 mb-1">Hora Llegada</div>
                            <div class="text-base font-bold ${bus.trip_end_formatted === 'En curso' ? 'text-yellow-600' : 'text-gray-800'}">
                                ${bus.trip_end_formatted}
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                        <div class="text-xs text-green-700 font-semibold mb-1">Monto Acumulado</div>
                        <div class="text-3xl font-bold text-green-700">Bs ${bus.trip_earnings}</div>
                    </div>

                    ${bus.speed ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                            <div class="text-xs text-gray-500 mb-1">Velocidad</div>
                            <div class="text-lg font-bold text-gray-800">${bus.speed.toFixed(1)} km/h</div>
                        </div>
                    ` : ''}

                    <div class="text-xs text-gray-500 text-center pt-2 border-t">
                        Última actualización: ${bus.last_update}
                    </div>
                </div>
            `;

            document.getElementById('busInfo').innerHTML = html;
        }

        // Event listener para filtro
        document.getElementById('rutaFilter').addEventListener('change', loadActiveBuses);

        // Configuración de Google Maps API Key
        const GOOGLE_MAPS_API_KEY = '{{ env("GOOGLE_MAPS_API_KEY", "") }}';
        const HAS_API_KEY = GOOGLE_MAPS_API_KEY && GOOGLE_MAPS_API_KEY !== '' && GOOGLE_MAPS_API_KEY !== 'TU_API_KEY_AQUI';

        // Solo cargar Google Maps si hay API Key válida
        if (HAS_API_KEY) {
            window.initMap = initMap;

            // Cargar script de Google Maps dinámicamente
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&callback=initMap&loading=async`;
            script.async = true;
            script.defer = true;
            script.onerror = function() {
                console.error('Error al cargar Google Maps');
                initMap(); // Iniciar sin mapa
            };
            document.head.appendChild(script);

            // Timeout de seguridad
            setTimeout(function() {
                if (!hasGoogleMaps) {
                    console.warn('Google Maps no se cargó en 5 segundos. Iniciando sin mapa.');
                    initMap();
                }
            }, 5000);
        } else {
            // No hay API Key, iniciar sin mapa
            console.info('Google Maps API Key no configurada. Iniciando en modo lista.');
            initMap();
        }
    </script>
    @endpush
</x-app-layout>
