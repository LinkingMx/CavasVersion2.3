<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Nicho</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        h1 { font-size: 1.5em; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        th { background: #f5f5f5; }
        @media print {
            button { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>Nicho: {{ $nicho->identifier }}</h1>
    <p><strong>Restaurante:</strong> {{ $nicho->restaurant->name }}</p>
    <p><strong>Cliente:</strong> {{ $nicho->customer->name }}</p>
    <p><strong>Información adicional:</strong> {{ $nicho->additional_info }}</p>
    <h2>Inventario actual</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
        @forelse($nicho->products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ number_format($product->pivot->quantity, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">No hay productos en este nicho.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <h2>Estado de cuenta por botella</h2>
    @foreach($nicho->products as $product)
        <h3>{{ $product->name }}</h3>
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
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Saldo</th>
                    <th>Notas</th>
                    <th>Ticket</th>
                </tr>
            </thead>
            <tbody>
            @foreach($movimientos as $i => $mov)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($mov['date'])->format('d/m/Y H:i') }}</td>
                    <td>{{ $mov['type'] === 'addition' ? 'Adición' : 'Consumo' }}</td>
                    <td style="text-align:right;">{{ number_format($mov['quantity'], 2) }}</td>
                    <td style="text-align:right;">{{ number_format($saldos[$i], 2) }}</td>
                    <td>{{ $mov['notes'] }}</td>
                    <td>{{ $mov['ticket'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <p>No hay movimientos para este producto.</p>
        @endif
    @endforeach
    <button onclick="window.print()">Imprimir</button>
</body>
</html>
