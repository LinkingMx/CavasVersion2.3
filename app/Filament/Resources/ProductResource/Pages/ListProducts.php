<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Jobs\ProductImportJob;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Nuestro nuevo botón de "Importar"
            Actions\Action::make('importProducts')
                ->label('Importar Productos')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                // Aquí definimos el formulario que aparecerá en el modal
                ->form([
                    Placeholder::make('download_template')
                        ->label('Paso 1: Descargar Plantilla')
                        ->content('Descarga la plantilla de Excel para asegurar que tus datos tengan el formato correcto.')
                        ->helperText('Haz clic en el botón de abajo para descargar.'),

                    // Componente personalizado para mostrar el enlace de descarga
                    ViewField::make('download_link')
                        ->label('')
                        ->view('filament.forms.components.download-template-link'),

                    FileUpload::make('attachment')
                        ->label('Paso 2: Subir Archivo')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'text/csv', // .csv
                        ])
                        ->maxSize(51200) // Límite de 50 MB para archivos grandes
                        ->disk('local') // Disco de almacenamiento temporal
                        ->directory('imports') // Carpeta de almacenamiento temporal
                        ->helperText('Máximo 50MB. Para archivos con más de 1000 registros, el procesamiento puede tardar varios minutos.'),
                ])
                // Esta es la lógica que se ejecuta al hacer clic en el botón final del modal
                ->action(function (array $data) {
                    // 1. Obtiene al usuario que está realizando la acción
                    $user = auth()->user();

                    // 2. Almacena el archivo temporalmente y obtiene su ruta
                    // Como el disco 'local' está configurado con root en 'app/private',
                    // necesitamos usar la ruta completa del archivo
                    $filePath = storage_path('app/private/'.$data['attachment']);

                    // 3. Despacha el Job a la cola para procesamiento en segundo plano
                    ProductImportJob::dispatch($filePath, $user);

                    // 4. Notifica al usuario que el proceso ha comenzado
                    \Filament\Notifications\Notification::make()
                        ->title('La importación ha comenzado')
                        ->body('Recibirás una notificación cuando el proceso haya terminado.')
                        ->success()
                        ->send();
                })
                // Evita que el modal se cierre inmediatamente
                ->modalCloseButton(false)
                ->modalWidth('xl'),
        ];
    }
}
