<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNicho extends CreateRecord
{
    protected static string $resource = NichoResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Nicho Creado')
            ->icon('heroicon-o-check-circle')
            ->body("El nicho '{$this->record->identifier}' ha sido creado exitosamente.");
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
