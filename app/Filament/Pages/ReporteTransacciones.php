<?php

namespace App\Filament\Resources\ReporteTransaccionesResource\Pages;

use App\Filament\Resources\ReporteTransaccionesResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReporteTransacciones extends ListRecords
{
    protected static string $resource = ReporteTransaccionesResource::class;

    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 100;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}