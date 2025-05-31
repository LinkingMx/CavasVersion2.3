@php
    use App\Models\Transaction;
    use App\Models\Nicho;
    use App\Models\Product;
    use Illuminate\Support\Facades\Request;
    use Illuminate\Support\Facades\DB;

    // Filtros (sin cambios en la lógica PHP)
    $filters = [
        'nicho_id' => Request::get('nicho_id'),
        'type' => Request::get('type'),
        'date_from' => Request::get('date_from'),
        'date_to' => Request::get('date_to'),
    ];

    $query = Transaction::query()->with(['nicho', 'details.product']);
    if ($filters['nicho_id']) {
        $query->where('nicho_id', $filters['nicho_id']);
    }
    if ($filters['type']) {
        $query->where('type', $filters['type']);
    }
    if ($filters['date_from']) {
        $query->whereDate('transaction_date', '>=', $filters['date_from']);
    }
    if ($filters['date_to']) {
        $query->whereDate('transaction_date', '<=', $filters['date_to']);
    }
    $transactions = $query->orderByDesc('transaction_date')->paginate(20);
    $nichos = Nicho::pluck('identifier', 'id');
    $types = [
        'addition' => 'Adición',
        'consumption' => 'Consumo',
    ];
@endphp

<x-filament-panels::page>
    {{-- Sección de Filtros Mejorada --}}
    <div class="mb-8 p-4 md:p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg transition-colors duration-300">
        <form method="GET"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-x-6 gap-y-4 items-end">
            <div>
                <label for="nicho_id_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nicho</label>
                <select name="nicho_id" id="nicho_id_filter"
                    class="filament-forms-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-primary-500 dark:focus:border-primary-400 focus:ring focus:ring-primary-200 dark:focus:ring-primary-600/50 transition duration-150">
                    <option value="">Todos los Nichos</option>
                    @foreach ($nichos as $id => $name)
                        <option value="{{ $id }}" @selected(request('nicho_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type_filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipo
                    de Transacción</label>
                <select name="type" id="type_filter"
                    class="filament-forms-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-primary-500 dark:focus:border-primary-400 focus:ring focus:ring-primary-200 dark:focus:ring-primary-600/50 transition duration-150">
                    <option value="">Ambos Tipos</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Desde</label>
                <input type="date" name="date_from" id="date_from_filter" value="{{ request('date_from') }}"
                    class="filament-forms-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-primary-500 dark:focus:border-primary-400 focus:ring focus:ring-primary-200 dark:focus:ring-primary-600/50 transition duration-150" />
            </div>
            <div>
                <label for="date_to_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Hasta</label>
                <input type="date" name="date_to" id="date_to_filter" value="{{ request('date_to') }}"
                    class="filament-forms-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-primary-500 dark:focus:border-primary-400 focus:ring focus:ring-primary-200 dark:focus:ring-primary-600/50 transition duration-150" />
            </div>
            {{-- Ajuste para que el botón ocupe su columna o se alinee bien en mobile --}}
            <div class="sm:col-span-2 lg:col-span-4 xl:col-span-1 flex items-end">
                <button type="submit"
                    class="filament-button filament-button-size-md filament-button-primary w-full justify-center transition-all duration-150 ease-in-out hover:shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                            clip-rule="evenodd" />
                    </svg>
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de Transacciones Mejorada --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0 z-10"> {{-- Sticky header --}}
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Fecha</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Nicho</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tipo</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Productos y Cantidades</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('D MMM YYYY, HH:mm') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $transaction->nicho->identifier ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if ($transaction->type === 'addition') bg-green-100 text-green-700 dark:bg-green-700/30 dark:text-green-300
                                    @elseif($transaction->type === 'consumption')
                                        bg-red-100 text-red-700 dark:bg-red-700/30 dark:text-red-300
                                    @else
                                        bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200 @endif
                                ">
                                    {{ $types[$transaction->type] ?? ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                @if ($transaction->details->isNotEmpty())
                                    <ul class="space-y-1">
                                        @foreach ($transaction->details as $detail)
                                            <li class="flex items-center">
                                                <span
                                                    class="font-semibold text-gray-700 dark:text-gray-200">{{ $detail->product->name ?? 'Producto Desconocido' }}</span>:
                                                <span
                                                    class="ml-1.5 font-mono text-primary-600 dark:text-primary-400 {{ $transaction->type === 'addition' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $transaction->type === 'addition' ? '+' : '-' }}{{ abs($detail->quantity_change) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Sin detalles</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate"
                                title="{{ $transaction->notes }}">
                                {{ Str::limit($transaction->notes, 50) ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    {{-- Icono Opcional --}}
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-6-6m0 0l-6-6m6 6l-6 6m6-6l6-6" />
                                    </svg>
                                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No se encontraron
                                        transacciones</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500">Intenta ajustar los filtros de
                                        búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    @if ($transactions->hasPages())
        <div class="mt-6 md:mt-8 px-2">
            {{-- Filament suele estilizar bien la paginación por defecto.
             Asegúrate que tu AppServiceProvider tenga TailwindPagination::defaultView('tailwind');
             o que Filament lo maneje internamente.
        --}}
            {{ $transactions->withQueryString()->links() }}
        </div>
    @endif
</x-filament-panels::page>
