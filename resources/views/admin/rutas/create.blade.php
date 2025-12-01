<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-red-700 font-bold">Por favor, corrige los siguientes errores:</p>
                    </div>
                    <ul class="list-disc list-inside text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.rutas.store') }}">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Nombre de la Línea
                    </label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej: Línea 1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Número de Línea</label>
                    <input type="text" name="linea_numero" value="{{ old('linea_numero') }}" maxlength="50" placeholder="Ej: 10, 12A, Micro A"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Descripción General (Opcional)
                    </label>
                    <textarea name="descripcion" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('descripcion') }}</textarea>
                </div>

                <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                    <h3 class="text-lg font-bold text-blue-800 mb-3">🔵 Ruta de IDA</h3>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción de Ida</label>
                    <textarea name="ruta_ida_descripcion" rows="3" placeholder="Ej: Terminal - Plaza - Centro - Universidad"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('ruta_ida_descripcion') }}</textarea>
                </div>

                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <h3 class="text-lg font-bold text-green-800 mb-3">🟢 Ruta de VUELTA</h3>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción de Vuelta</label>
                    <textarea name="ruta_vuelta_descripcion" rows="3" placeholder="Ej: Universidad - Centro - Plaza - Terminal"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('ruta_vuelta_descripcion') }}</textarea>
                </div>

                <div class="mb-6 grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Tarifa Base (Bs.)
                        </label>
                        <input type="number" name="tarifa_base" step="0.01" min="0" value="{{ old('tarifa_base', '2.30') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tarifa Adulto (Bs.)</label>
                        <input type="number" name="tarifa_adulto" step="0.01" min="0" value="{{ old('tarifa_adulto', '2.30') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tarifa con Descuento (Bs.)</label>
                        <input type="number" name="tarifa_descuento" step="0.01" min="0" value="{{ old('tarifa_descuento', '1.00') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-600 mt-1">Para menores, mayores y estudiantes</p>
                    </div>
                </div>

                <div class="mb-6 flex items-center gap-3">
                    <input id="activa" type="checkbox" name="activa" value="1" {{ old('activa', 1) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="activa" class="text-sm font-bold text-gray-700">Ruta Activa</label>
                </div>

                <div class="flex justify-between items-center mt-8">
                    <a href="{{ route('admin.rutas.index') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 text-white rounded-xl shadow-lg hover:bg-gray-700 transition-all duration-200 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Volver
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl shadow-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                        Guardar Ruta
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
