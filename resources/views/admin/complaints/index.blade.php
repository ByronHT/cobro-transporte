<x-app-layout>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- Encabezado --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Quejas de Pasajeros</h1>
                <p class="text-gray-600 text-sm">Gestion y seguimiento de quejas reportadas</p>
            </div>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 text-white rounded-xl shadow-lg hover:bg-gray-700 transition-all duration-200 font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Panel
            </a>
        </div>

        {{-- Filtros de estado --}}
        <div class="mb-6 flex justify-between items-center">
            <div class="flex gap-2">
                <a href="{{ route('admin.complaints.index', ['status' => 'all']) }}"
                   class="px-4 py-2 {{ $status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded-lg hover:bg-blue-500 hover:text-white transition-all duration-200 font-semibold">
                    Todas ({{ App\Models\Complaint::count() }})
                </a>
                <a href="{{ route('admin.complaints.index', ['status' => 'pending']) }}"
                   class="px-4 py-2 {{ $status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded-lg hover:bg-yellow-500 hover:text-white transition-all duration-200 font-semibold">
                    Pendientes ({{ App\Models\Complaint::where('status', 'pending')->count() }})
                </a>
                <a href="{{ route('admin.complaints.index', ['status' => 'reviewed']) }}"
                   class="px-4 py-2 {{ $status === 'reviewed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded-lg hover:bg-green-500 hover:text-white transition-all duration-200 font-semibold">
                    Atendidas ({{ App\Models\Complaint::where('status', 'reviewed')->count() }})
                </a>
            </div>
        </div>

        {{-- Mensaje de éxito --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <p class="text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Tabla de quejas --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-red-700">
                <h3 class="font-bold text-xl text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Listado de Quejas - {{ $status === 'pending' ? 'Pendientes' : ($status === 'reviewed' ? 'Atendidas' : 'Todas') }}
                </h3>
            </div>

            @if($complaints->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-semibold">No hay quejas {{ $status !== 'all' ? 'con este estado' : 'registradas' }}</p>
                </div>
            @else
                <div class="divide-y divide-gray-200">
                    @foreach($complaints as $complaint)
                        <div class="complaint-item" id="complaint-{{ $complaint->id }}">
                            {{-- Vista compacta del registro --}}
                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $complaint->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $complaint->status === 'pending' ? 'Pendiente' : 'Atendida' }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $complaint->created_at->format('d/m/Y H:i') }}</span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <p class="text-gray-700">
                                                <strong>Pasajero:</strong> {{ $complaint->passenger->name }}
                                            </p>
                                            <p class="text-gray-700">
                                                <strong>Chofer:</strong> {{ $complaint->driver->name }}
                                            </p>
                                            <p class="text-gray-600">
                                                <strong>Ruta:</strong> {{ $complaint->ruta->nombre ?? 'N/A' }}
                                            </p>
                                            <p class="text-gray-600">
                                                <strong>Bus:</strong> {{ $complaint->trip->bus->plate ?? $complaint->bus->plate ?? 'N/A' }}
                                            </p>
                                        </div>

                                        <p class="text-sm text-gray-800 mt-2 line-clamp-2">
                                            <strong>Motivo:</strong> {{ $complaint->reason }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        @if($complaint->status === 'pending')
                                            <button
                                                onclick="toggleComplaintForm({{ $complaint->id }})"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm whitespace-nowrap">
                                                Atender
                                            </button>
                                        @else
                                            <button
                                                onclick="toggleComplaintForm({{ $complaint->id }})"
                                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-semibold text-sm whitespace-nowrap">
                                                Modificar
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Formulario expandible --}}
                            <div id="form-{{ $complaint->id }}" class="hidden bg-gray-50 border-t border-gray-200">
                                <div class="p-6">
                                    <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
                                        <h4 class="font-semibold text-gray-800 mb-3">Detalles de la Queja</h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3 text-sm">
                                            <p class="text-gray-700">
                                                <strong>Pasajero:</strong> {{ $complaint->passenger->name }} ({{ $complaint->passenger->email }})
                                            </p>
                                            <p class="text-gray-700">
                                                <strong>Chofer:</strong> {{ $complaint->driver->name }} ({{ $complaint->driver->email }})
                                            </p>
                                            <p class="text-gray-600">
                                                <strong>Ruta:</strong> {{ $complaint->ruta->nombre ?? 'N/A' }} - {{ $complaint->ruta->descripcion ?? '' }}
                                            </p>
                                            <p class="text-gray-600">
                                                <strong>Bus:</strong> {{ $complaint->trip->bus->plate ?? $complaint->bus->plate ?? 'N/A' }}
                                            </p>
                                            <p class="text-gray-600">
                                                <strong>Fecha de queja:</strong> {{ $complaint->created_at->format('d/m/Y H:i') }}
                                            </p>
                                            @if($complaint->reviewed_at)
                                                <p class="text-gray-600">
                                                    <strong>Atendida por:</strong> {{ $complaint->reviewer->name ?? 'N/A' }} el {{ $complaint->reviewed_at->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="mb-3">
                                            <p class="text-sm font-semibold text-gray-700 mb-1">Motivo de la queja:</p>
                                            <p class="text-sm text-gray-800 bg-yellow-50 p-3 rounded-lg border-l-4 border-yellow-400">{{ $complaint->reason }}</p>
                                        </div>

                                        @if($complaint->photo_path)
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 mb-1">Evidencia fotográfica:</p>
                                                <a href="{{ asset('storage/' . $complaint->photo_path) }}" target="_blank" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                                    </svg>
                                                    Ver foto adjunta
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <form action="{{ route('admin.complaints.update', $complaint->id) }}" method="POST" class="bg-white p-4 rounded-lg border border-gray-200">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="pending" {{ $complaint->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                <option value="reviewed" {{ $complaint->status === 'reviewed' ? 'selected' : '' }}>Atendida</option>
                                            </select>
                                        </div>

                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Respuesta del administrador</label>
                                            <textarea
                                                name="admin_response"
                                                rows="4"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Escribe una respuesta para el pasajero...">{{ $complaint->admin_response }}</textarea>
                                        </div>

                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                onclick="toggleComplaintForm({{ $complaint->id }})"
                                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-semibold">
                                                Cancelar
                                            </button>
                                            <button
                                                type="submit"
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                                                Actualizar Queja
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Paginación --}}
                @if($complaints->hasPages())
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        {{ $complaints->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleComplaintForm(complaintId) {
            const form = document.getElementById('form-' + complaintId);

            // Cerrar todos los demás formularios abiertos
            document.querySelectorAll('[id^="form-"]').forEach(otherForm => {
                if (otherForm.id !== 'form-' + complaintId && !otherForm.classList.contains('hidden')) {
                    otherForm.classList.add('hidden');
                }
            });

            // Toggle del formulario actual
            form.classList.toggle('hidden');

            // Scroll suave al formulario si se está abriendo
            if (!form.classList.contains('hidden')) {
                setTimeout(() => {
                    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
    </script>
    @endpush
</x-app-layout>
