<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNicho extends EditRecord
{
    protected static string $resource = NichoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Nicho Actualizado')
            ->icon('heroicon-o-check-circle')
            ->body("El nicho '{$this->record->identifier}' ha sido actualizado exitosamente.");
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
