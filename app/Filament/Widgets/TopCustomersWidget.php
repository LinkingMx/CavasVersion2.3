<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopCustomersWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Clientes con Más Nichos';
    
    protected static ?int $sort = 4;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Obtener el usuario actual
        $user = Auth::user();
        
        // Inicializar la consulta base
        $query = Customer::query()
            ->select('customers.id', 'customers.name')
            ->withCount('nichos');
            
        // Si el usuario tiene restricciones de restaurantes, limitar los clientes
        // a aquellos que tienen nichos en esos restaurantes
        if ($user && method_exists($user, 'restaurants')) {
            $restaurantIds = $user->restaurants()->pluck('restaurants.id');
            if (!empty($restaurantIds)) {
                $query->whereHas('nichos', function($q) use ($restaurantIds) {
                    $q->whereIn('restaurant_id', $restaurantIds);
                });
            }
        }
        
        // Obtener los 5 clientes con más nichos
        $topCustomers = $query
            ->orderByDesc('nichos_count')
            ->limit(5)
            ->get();
        
        // Preparar los datos para el gráfico
        $labels = [];
        $nichosData = [];
        $backgroundColors = [
            'rgba(255, 99, 132, 0.8)',  // Rojo
            'rgba(54, 162, 235, 0.8)',  // Azul
            'rgba(255, 206, 86, 0.8)',  // Amarillo
            'rgba(75, 192, 192, 0.8)',  // Verde
            'rgba(153, 102, 255, 0.8)', // Púrpura
        ];
        
        foreach ($topCustomers as $index => $customer) {
            // Mostrar solo el nombre del cliente
            $labels[] = $customer->name;
            
            // Agregar el total de nichos
            $nichosData[] = $customer->nichos_count ?? 0;
        }
        
        // Retornar los datos formateados para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Cantidad de Nichos',
                    'data' => $nichosData,
                    'backgroundColor' => $backgroundColors,
                    'hoverOffset' => 4
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'font' => [
                            'size' => 10, // Tamaño de letra más pequeño para las etiquetas
                        ]
                    ]
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " nichos"; }'
                    ]
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '30%', // Tamaño del agujero central (0% para un círculo completo)
        ];
    }
}
