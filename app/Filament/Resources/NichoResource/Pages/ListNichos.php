<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNichos extends ListRecords
{
    protected static string $resource = NichoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
