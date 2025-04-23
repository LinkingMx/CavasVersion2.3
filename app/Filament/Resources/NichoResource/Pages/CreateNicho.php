<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateNicho extends CreateRecord
{
    protected static string $resource = NichoResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Nicho Created')
            ->icon('heroicon-o-check-circle')
            ->body("The nicho '{$this->record->identifier}' has been created successfully.");
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
