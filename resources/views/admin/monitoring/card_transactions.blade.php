<x-app-layout>
    <x-slot name="header"><h2>Monitoreo de Transacciones por Tarjeta</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-6">

                <form method="GET" action="{{ route('admin.monitoring.card_transactions') }}" class="mb-6">
                    <div class="flex items-center space-x-4">
                        <label for="card_id" class="block text-sm font-bold text-gray-700">Seleccionar Tarjeta:</label>
                        <select name="card_id" id="card_id" class="form-select mt-1 block w-1/2 rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Todas las Tarjetas --</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" {{ $selectedCardId == $card->id ? 'selected' : '' }}>
                                    {{ $card->uid }} (Pasajero: {{ $card->passenger->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filtrar</button>
                    </div>
                </form>

                <h3 class="text-lg font-bold mb-4">Transacciones @if($selectedCardId) de la Tarjeta {{ $cards->firstWhere('id', $selectedCardId)->uid }} @endif</h3>

                @if($transactions->isEmpty())
                    <p class="text-gray-600">No hay transacciones registradas para la selección actual.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Transacción</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chofer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Línea</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->type }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($transaction->amount, 2) }} Bs</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->description ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->trip->driver->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->trip->bus->ruta->nombre ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
