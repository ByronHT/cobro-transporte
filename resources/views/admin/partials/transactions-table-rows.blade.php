@forelse($transactions as $transaction)
    <tr class="hover:bg-green-50 transition-colors duration-150">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">{{ optional($transaction->card->passenger)->name ?? 'Sin asignar' }}</span>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="text-sm font-medium text-gray-900">{{ optional($transaction->ruta)->nombre ?? '-' }}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            @if($transaction->bus_id || $transaction->driver_id)
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ optional($transaction->bus)->plate ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ optional($transaction->driver)->name ?? '-' }}</div>
                </div>
            @else
                <span class="text-sm font-medium text-gray-900">-</span>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                {{ $transaction->created_at->format('d/m/Y H:i') }}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            @if($transaction->type === 'fare')
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                    💳 Pago
                </span>
            @elseif($transaction->type === 'refund')
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                    🔄 Devolución
                </span>
            @elseif($transaction->type === 'refund_reversal')
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">
                    ⚠️ Reversión
                </span>
            @else
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    💰 Recarga
                </span>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2">
                @if($transaction->type === 'fare')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-bold text-red-600">Bs {{ number_format(abs($transaction->amount), 2) }}</span>
                @elseif($transaction->type === 'refund')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-bold text-purple-600">Bs {{ number_format(abs($transaction->amount), 2) }}</span>
                @elseif($transaction->type === 'refund_reversal')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-bold text-amber-600">Bs {{ number_format(abs($transaction->amount), 2) }}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-bold text-green-600">Bs {{ number_format(abs($transaction->amount), 2) }}</span>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No hay transacciones que mostrar</p>
        </td>
    </tr>
@endforelse
