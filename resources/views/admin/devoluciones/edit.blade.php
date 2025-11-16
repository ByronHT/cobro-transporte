<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
        {{-- Encabezado --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">✏️ Editar Devolución #{{ $devolucion->id }}</h1>
                <p class="text-gray-600 mt-1">Modificar información de la devolución</p>
            </div>
            <a href="{{ route('admin.devoluciones.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver
            </a>
        </div>

        {{-- Formulario --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 space-y-6">
                {{-- Información de la devolución (solo lectura) --}}
                <div class="border-b pb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Información de la Devolución</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Pasajero</label>
                            <p class="text-lg font-medium text-gray-900">{{ $devolucion->transaction->card->passenger->name ?? 'Sin asignar' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Chofer que Aprobó</label>
                            <p class="text-lg font-medium text-gray-900">{{ $devolucion->transaction->trip->driver->name ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Bus</label>
                            <p class="text-lg font-medium text-gray-900">{{ $devolucion->transaction->trip->bus->plate ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Ruta</label>
                            <p class="text-lg font-medium text-gray-900">{{ $devolucion->transaction->trip->ruta->nombre ?? '-' }}</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 border-2 border-green-300">
                            <label class="text-sm font-semibold text-gray-600">Monto Devuelto</label>
                            <p class="text-2xl font-bold text-green-600">Bs {{ number_format(abs($devolucion->transaction->amount), 2) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <label class="text-sm font-semibold text-gray-600">Fecha de Devolución</label>
                            <p class="text-lg font-medium text-gray-900">{{ $devolucion->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Formulario editable --}}
                <form action="{{ route('admin.devoluciones.update', $devolucion->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Motivo de la Devolución
                            </label>
                            <textarea name="reason" rows="4"
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Ingrese el motivo o modificación...">{{ old('reason', $devolucion->reason) }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                                </svg>
                                Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Zona de peligro --}}
                <div class="border-t pt-6 mt-6">
                    <h3 class="text-lg font-bold text-red-600 mb-4">⚠️ Zona de Peligro</h3>
                    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
                        <p class="text-sm text-gray-700 mb-4">
                            Al revertir esta devolución, el monto será <strong>restado del saldo de la tarjeta</strong> del pasajero
                            y la transacción será eliminada. Esta acción es útil si el chofer aprobó una devolución por error.
                        </p>
                        <form action="{{ route('admin.devoluciones.revertir', $devolucion->id) }}" method="POST"
                              onsubmit="return confirm('⚠️ ADVERTENCIA: ¿Está completamente seguro de revertir esta devolución?\n\nSe restará Bs {{ number_format(abs($devolucion->transaction->amount), 2) }} del saldo de {{ $devolucion->transaction->card->passenger->name ?? "la tarjeta" }}.\n\nEsta acción NO se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                </svg>
                                Revertir Devolución
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
