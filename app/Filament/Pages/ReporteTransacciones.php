<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReporteTransacciones extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reporte de transacciones';

    protected static ?string $title = 'Reporte de transacciones';

    protected static string $view = 'filament.pages.transactions-report';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 100;
}
