<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Nicho;
use App\Models\NichoProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TopNichosStockWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Nichos con Mayor Stock';
    
    protected static ?int $sort = 2;
    
    // Establecer el tamaño del widget - opciones: md, lg, xl, 2xl, etc.
    protected static ?string $maxHeight = '300px';
    
    protected function getData(): array
    {
        // Obtener el usuario actual
        $user = Auth::user();
        
        // Inicializar la consulta base
        $query = Nicho::query()
            ->select('nichos.id', 'nichos.identifier', 'restaurants.name as restaurant_name', 'customers.name as customer_name')
            ->leftJoin('restaurants', 'nichos.restaurant_id', '=', 'restaurants.id')
            ->leftJoin('customers', 'nichos.customer_id', '=', 'customers.id')
            ->withSum('products as total_stock', 'nicho_product.quantity');
        
        // Si el usuario tiene restricciones de restaurantes, aplicarlas
        if ($user && method_exists($user, 'restaurants')) {
            $restaurantIds = $user->restaurants()->pluck('restaurants.id');
            $query->whereIn('restaurant_id', $restaurantIds);
        }
        
        // Obtener los 5 nichos con mayor stock
        $topNichos = $query
            ->orderByDesc('total_stock')
            ->limit(5)
            ->get();
        
        // Preparar los datos para el gráfico
        $labels = [];
        $stockData = [];
        
        foreach ($topNichos as $nicho) {
            // Mostrar solo el identificador del nicho sin el restaurante
            $labels[] = $nicho->identifier;
            
            // Agregar el total de stock a los datos
            $stockData[] = (float) $nicho->total_stock ?? 0;
        }
        
        // Retornar los datos formateados para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Stock Total',
                    'data' => $stockData,
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.8)', // Azul
                        'rgba(255, 99, 132, 0.8)',  // Rojo
                        'rgba(75, 192, 192, 0.8)',  // Verde
                        'rgba(255, 206, 86, 0.8)',  // Amarillo
                        'rgba(153, 102, 255, 0.8)', // Púrpura
                    ],
                    'borderColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(75, 192, 192)',
                        'rgb(255, 206, 86)',
                        'rgb(153, 102, 255)',
                    ],
                    'borderWidth' => 1
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    // Configurar opciones adicionales del gráfico
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad de Stock'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Nichos'
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 10, // Tamaño de letra más pequeño para las etiquetas
                        ]
                    ]
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'title' => 'function(context) { return "Nicho: " + context[0].label; }',
                        'label' => 'function(context) { return "Stock: " + context.parsed.y; }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
