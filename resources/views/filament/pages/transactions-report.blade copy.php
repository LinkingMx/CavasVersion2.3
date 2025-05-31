@php
use App\Models\Transaction;
use App\Models\Nicho;
use App\Models\Product;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

// Filtros
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
    <form method="GET" class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-4 p-4 bg-white rounded-lg shadow">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nicho</label>
            <select name="nicho_id" class="filament-forms-input w-full rounded border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200">
                <option value="">Todos</option>
                @foreach($nichos as $id => $name)
                    <option value="{{ $id }}" @selected(request('nicho_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
            <select name="type" class="filament-forms-input w-full rounded border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200">
                <option value="">Todos</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(request('type') == $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filament-forms-input w-full rounded border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200" />
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filament-forms-input w-full rounded border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200" />
        </div>
        <div class="flex items-end">
            <button type="submit" class="filament-button filament-button--primary w-full">Filtrar</button>
        </div>
    </form>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nicho</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Productos</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Notas</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->nicho->identifier ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $transaction->type === 'addition' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $types[$transaction->type] ?? $transaction->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <ul class="list-disc pl-4">
                                @foreach($transaction->details as $detail)
                                    <li>
                                        <span class="font-semibold">{{ $detail->product->name ?? '-' }}</span>:
                                        <span class="font-mono">{{ $detail->quantity_change }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-400">No hay transacciones para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 flex justify-center">
        {{ $transactions->withQueryString()->links() }}
    </div>
</x-filament-panels::page>
