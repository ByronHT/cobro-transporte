<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6">

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.trips.update', $trip) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Fecha</label>
                        <input type="date" name="fecha"
                               value="{{ old('fecha', $trip->fecha ? \Carbon\Carbon::parse($trip->fecha)->format('Y-m-d') : '') }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Ruta</label>
                        <select name="ruta_id" class="select2 w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Seleccione una ruta</option>
                            @foreach($rutas as $ruta)
                                <option value="{{ $ruta->id }}"
                                    {{ old('ruta_id', $trip->ruta_id) == $ruta->id ? 'selected' : '' }}>
                                    {{ $ruta->nombre }} - {{ $ruta->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Bus</label>
                        <select name="bus_id" class="select2 w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Seleccione un bus</option>
                            @foreach($buses as $bus)
                                <option value="{{ $bus->id }}"
                                    {{ old('bus_id', $trip->bus_id) == $bus->id ? 'selected' : '' }}>
                                    {{ $bus->plate }} - {{ $bus->brand }} {{ $bus->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Chofer</label>
                        <select name="driver_id" class="select2 w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Seleccione un chofer</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    {{ old('driver_id', $trip->driver_id) == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Hora de Inicio</label>
                        <input type="datetime-local" name="inicio"
                               value="{{ old('inicio', $trip->inicio ? \Carbon\Carbon::parse($trip->inicio)->format('Y-m-d\TH:i') : '') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Hora de Fin</label>
                        <input type="datetime-local" name="fin"
                               value="{{ old('fin', $trip->fin ? \Carbon\Carbon::parse($trip->fin)->format('Y-m-d\TH:i') : '') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('admin.trips.index') }}"
                           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            ← Volver
                        </a>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Actualizar Viaje
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
