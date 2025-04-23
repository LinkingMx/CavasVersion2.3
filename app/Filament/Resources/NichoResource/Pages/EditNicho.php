<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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
            ->title('Nicho Updated')
            ->icon('heroicon-o-check-circle')
            ->body("The nicho '{$this->record->identifier}' has been updated successfully.");
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
