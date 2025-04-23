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
    <button onclick="window.print()">Imprimir</button>
</body>
</html>
