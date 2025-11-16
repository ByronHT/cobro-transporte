<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6">

                <form method="POST" action="{{ route('admin.cards.update', $card->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-bold">UID</label>
                        <input type="text" name="uid" value="{{ old('uid', $card->uid) }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold">Balance Actual</label>
                        <p class="w-full bg-gray-100 p-2 rounded-md">{{ number_format($card->balance, 2) }} Bs.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold">Propietario</label>
                        <select name="passenger_id" id="passenger_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Seleccione un pasajero</option>
                            @foreach($passengers as $passenger)
                                <option value="{{ $passenger->id }}" {{ old('passenger_id', $card->passenger_id ?? '') == $passenger->id ? 'selected' : '' }}>
                                    {{ $passenger->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold">Estado</label>
                        <select name="active" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="1" {{ $card->active ? 'selected' : '' }}>Activa</option>
                            <option value="0" {{ !$card->active ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('admin.cards.index') }}"
                           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            ← Volver
                        </a>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Actualizar Tarjeta
                        </button>
                    </div>
                </form>

                <hr class="my-8">

                {{-- Formulario de Recarga --}}
                <h3 class="text-lg font-bold mb-4">Recargar Saldo</h3>

                {{-- Mostrar mensajes de éxito --}}
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Mostrar errores --}}
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.cards.recharge', $card->id) }}" id="rechargeForm">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-bold">Monto a Recargar</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                               placeholder="Ej: 10.50"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Confirmar Recarga
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    // Asegurar que el formulario de recarga se envíe correctamente
    document.addEventListener('DOMContentLoaded', function() {
        const rechargeForm = document.getElementById('rechargeForm');
        if (rechargeForm) {
            rechargeForm.addEventListener('submit', function(e) {
                console.log('Formulario de recarga enviándose...');
                // No prevenir default - dejar que se envíe normalmente
            });
        }
    });
</script>
@endpush
