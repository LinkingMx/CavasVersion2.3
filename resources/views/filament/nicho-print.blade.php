<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Nicho</title>
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            button { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()" class="bg-white text-black font-sans">
    <div class="max-w-3xl mx-auto p-6">
        <div class="flex flex-col items-center mb-6 bg-black p-6">
            <img src="{{ asset('storage/gcore-cavas.png') }}" alt="Logo Cavas" class="h-16 w-auto mb-2">
        </div>
        <h1 class="text-2xl font-bold mb-2">Nicho: {{ $nicho->identifier }}</h1>
        <div class="mb-4 text-sm">
            <p><span class="font-semibold">Restaurante:</span> {{ $nicho->restaurant->name }}</p>
            <p><span class="font-semibold">Cliente:</span> {{ $nicho->customer->name }}</p>
            <p><span class="font-semibold">Información adicional:</span> {{ $nicho->additional_info }}</p>
        </div>
        <h2 class="text-lg font-semibold mt-6 mb-2">Inventario actual</h2>
        <table class="w-full border border-black text-sm mb-8">
            <thead>
                <tr class="bg-black text-white">
                    <th class="border border-black px-2 py-1 font-semibold">Producto</th>
                    <th class="border border-black px-2 py-1 font-semibold">Cantidad</th>
                </tr>
            </thead>
            <tbody>
            @forelse($nicho->products as $product)
                <tr class="odd:bg-white even:bg-gray-100">
                    <td class="border border-black px-2 py-1">{{ $product->name }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ number_format($product->pivot->quantity, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="border border-black px-2 py-1 text-center">No hay productos en este nicho.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <h2 class="text-lg font-semibold mt-8 mb-2">Estado de cuenta por botella</h2>
        @foreach($nicho->products as $product)
            <h3 class="text-base font-bold mt-6 mb-2">{{ $product->name }}</h3>
            @php
                $movimientos = $nicho->transactions()
                    ->with(['details' => function($q) use ($product) {
                        $q->where('product_id', $product->id);
                    }])
                    ->orderBy('transaction_date')
                    ->get()
                    ->flatMap(function($tx) use ($product) {
                        return $tx->details->map(function($detail) use ($tx) {
                            return [
                                'date' => $tx->transaction_date,
                                'type' => $tx->type,
                                'quantity' => $detail->quantity_change,
                                'notes' => $tx->notes,
                                'ticket' => $tx->ticket_number,
                            ];
                        });
                    })->sortBy('date')->values();
                $saldo = 0;
                $saldos = [];
                foreach ($movimientos as $mov) {
                    $saldo += $mov['quantity'];
                    $saldos[] = $saldo;
                }
            @endphp
            @if($movimientos->count())
            <table class="w-full border border-black text-xs mb-8">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="border border-black px-2 py-1 font-semibold">Fecha</th>
                        <th class="border border-black px-2 py-1 font-semibold">Tipo</th>
                        <th class="border border-black px-2 py-1 font-semibold">Cantidad</th>
                        <th class="border border-black px-2 py-1 font-semibold">Saldo</th>
                        <th class="border border-black px-2 py-1 font-semibold">Notas</th>
                        <th class="border border-black px-2 py-1 font-semibold">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($movimientos as $i => $mov)
                    <tr class="odd:bg-white even:bg-gray-100">
                        <td class="border border-black px-2 py-1">{{ \Carbon\Carbon::parse($mov['date'])->format('d/m/Y H:i') }}</td>
                        <td class="border border-black px-2 py-1">{{ $mov['type'] === 'addition' ? 'Adición' : 'Consumo' }}</td>
                        <td class="border border-black px-2 py-1 text-right">{{ number_format($mov['quantity'], 2) }}</td>
                        <td class="border border-black px-2 py-1 text-right">{{ number_format($saldos[$i], 2) }}</td>
                        <td class="border border-black px-2 py-1">{{ $mov['notes'] }}</td>
                        <td class="border border-black px-2 py-1">{{ $mov['ticket'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
                <p class="text-xs italic text-gray-600 mb-6">No hay movimientos para este producto.</p>
            @endif
        @endforeach
        <button onclick="window.print()" class="mt-8 px-4 py-2 bg-black text-white rounded shadow hover:bg-gray-800 print:hidden">Imprimir</button>
    </div>
</body>
</html>
