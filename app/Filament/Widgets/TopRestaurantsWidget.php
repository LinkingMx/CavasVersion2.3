<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopRestaurantsWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Restaurantes con Más Nichos';
    
    protected static ?int $sort = 3;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Obtener el usuario actual
        $user = Auth::user();
        
        // Inicializar la consulta base
        $query = Restaurant::query()
            ->select('restaurants.id', 'restaurants.name')
            ->withCount('nichos');
            
        // Si el usuario tiene restricciones de restaurantes, aplicarlas
        if ($user && method_exists($user, 'restaurants')) {
            $restaurantIds = $user->restaurants()->pluck('restaurants.id');
            if (!empty($restaurantIds)) {
                $query->whereIn('restaurants.id', $restaurantIds);
            }
        }
        
        // Obtener los 5 restaurantes con más nichos
        $topRestaurants = $query
            ->orderByDesc('nichos_count')
            ->limit(5)
            ->get();
        
        // Preparar los datos para el gráfico
        $labels = [];
        $nichosData = [];
        
        foreach ($topRestaurants as $restaurant) {
            // Mostrar solo el nombre del restaurante
            $labels[] = $restaurant->name;
            
            // Agregar el total de nichos
            $nichosData[] = $restaurant->nichos_count ?? 0;
        }
        
        // Retornar los datos formateados para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Cantidad de Nichos',
                    'data' => $nichosData,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',  // Rojo
                        'rgba(54, 162, 235, 0.8)', // Azul
                        'rgba(255, 206, 86, 0.8)',  // Amarillo
                        'rgba(75, 192, 192, 0.8)',  // Verde
                        'rgba(153, 102, 255, 0.8)', // Púrpura
                    ],
                    'borderColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
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
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad de Nichos'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Restaurantes'
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
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
