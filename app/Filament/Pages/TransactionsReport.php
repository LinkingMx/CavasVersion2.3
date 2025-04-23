<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TransactionsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Reports';

    protected static string $view = 'filament.pages.transactions-report';
}
