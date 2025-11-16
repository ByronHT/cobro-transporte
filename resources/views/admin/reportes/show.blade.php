<x-app-layout>
    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8">
        {{-- Encabezado --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📄 Detalle del Reporte #{{ $reporte->id }}</h1>
                <p class="text-gray-600 mt-1">Información completa del reporte del chofer</p>
            </div>
            <a href="{{ route('admin.reportes.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Salir
            </a>
        </div>

        {{-- Contenido del reporte --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 space-y-6">
                {{-- Información del viaje --}}
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                        </svg>
                        Información del Viaje
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Chofer</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->driver->name ?? 'Sin asignar' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Bus</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->bus->plate ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Ruta</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->ruta->nombre ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Fecha del Viaje</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->fecha->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Hora Inicio</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->inicio ? $reporte->inicio->format('H:i:s') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Hora Fin</label>
                            <p class="text-lg font-medium text-gray-900">{{ $reporte->fin ? $reporte->fin->format('H:i:s') : '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Contenido del reporte --}}
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Descripción del Reporte
                    </h2>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4">
                        <p class="text-gray-800">{{ $reporte->reporte }}</p>
                    </div>
                </div>

                {{-- Foto adjunta --}}
                @if($reporte->photo_path)
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                            </svg>
                            Foto Adjunta
                        </h2>
                        <div class="bg-gray-100 rounded-lg p-4 flex justify-center">
                            <img src="{{ asset('storage/' . $reporte->photo_path) }}"
                                 alt="Foto del reporte"
                                 class="max-w-full max-h-96 rounded-lg shadow-lg">
                        </div>
                    </div>
                @endif

                {{-- Estado actual --}}
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Estado del Reporte
                    </h2>
                    <div class="flex items-center gap-4">
                        @if($reporte->status === 'pendiente')
                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                ⏳ Pendiente
                            </span>
                            <form action="{{ route('admin.reportes.marcar-atendido', $reporte->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-150">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Marcar como Atendido
                                </button>
                            </form>
                        @else
                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                ✅ Atendido
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
