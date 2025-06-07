<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use App\Models\Nicho;
use App\Models\NichoProduct;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewNicho extends ViewRecord
{
    protected static string $resource = NichoResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addBottles')
                ->label('Agregar Botellas')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalWidth('xl')
                ->form([
                    Forms\Components\Repeater::make('items')
                        ->label('Productos a Agregar')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Producto')
                                ->options(Product::pluck('name', 'id')->toArray())
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad Agregada')
                                ->numeric()
                                ->required(), // Remove minValue and step restrictions
                        ])
                        ->columns(2)
                        ->required()
                        ->addActionLabel('Agregar Otro Producto'),
                    Forms\Components\TextInput::make('ticket_number')
                        ->label('Número de Ticket (Opcional)')
                        ->nullable(),
                    Forms\Components\FileUpload::make('ticket_photo_path')
                        ->label('Foto del Ticket (Opcional)')
                        ->disk('public')
                        ->directory('ticket-photos')
                        ->image()
                        ->nullable(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas (Opcional)')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, $record) {
                    $nichoId = $record->id;
                    $transaction = null;
                    DB::transaction(function () use ($data, $nichoId, &$transaction) {
                        // Handle file upload
                        if (! empty($data['ticket_photo_path'])) {
                            $data['ticket_photo_path'] = is_array($data['ticket_photo_path']) ? $data['ticket_photo_path'][0] : $data['ticket_photo_path'];
                        }
                        $transaction = Transaction::query()->create([
                            'nicho_id' => $nichoId,
                            'type' => 'addition',
                            'ticket_number' => $data['ticket_number'] ?? null,
                            'ticket_photo_path' => $data['ticket_photo_path'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'transaction_date' => now(),
                        ]);
                        foreach ($data['items'] as $item) {
                            $transaction->details()->create([
                                'product_id' => $item['product_id'],
                                'quantity_change' => $item['quantity'],
                            ]);

                            // Buscar si ya existe un registro para este producto en este nicho
                            $inventory = NichoProduct::where('nicho_id', $nichoId)
                                ->where('product_id', $item['product_id'])
                                ->first();

                            if ($inventory) {
                                // Si existe, actualizar la cantidad
                                $inventory->quantity = $inventory->quantity + $item['quantity'];
                                $inventory->save();
                            } else {
                                // Si no existe, crear un nuevo registro
                                NichoProduct::create([
                                    'nicho_id' => $nichoId,
                                    'product_id' => $item['product_id'],
                                    'quantity' => $item['quantity'],
                                ]);
                            }
                        }
                    });
                    Notification::make()
                        ->title('Botellas Agregadas Exitosamente')
                        ->icon('heroicon-o-check-circle')
                        ->body("Nuevos productos agregados al Nicho '{$record->identifier}'. ID de Transacción: {$transaction->id}")
                        ->success()
                        ->send();
                }),
            Action::make('recordConsumption')
                ->label('Registrar Consumo')
                ->icon('heroicon-o-minus-circle')
                ->color('warning')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Registrar Consumo')
                ->form(function (Action $action, Nicho $record): array {
                    $inventoryItems = $record->products()->wherePivot('quantity', '>', 0)->get();
                    $schema = [];
                    $schema[] = Forms\Components\Placeholder::make('info')->content('Ingrese la cantidad FINAL restante para cada producto consumido.');
                    if ($inventoryItems->isEmpty()) {
                        $schema[] = Forms\Components\Placeholder::make('empty')->content('No hay productos con cantidad positiva en este nicho para consumir.');
                    } else {
                        foreach ($inventoryItems as $item) {
                            $currentQty = $item->pivot->quantity;
                            $schema[] = Forms\Components\TextInput::make('remaining_quantity.'.$item->id)
                                ->label($item->name.' (Actual: '.number_format($currentQty, 2).')')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($currentQty)
                                ->step(0.1)
                                ->nullable()
                                ->placeholder('Ingrese cantidad final (ej. 0.7)');
                        }
                    }
                    $schema[] = Forms\Components\Textarea::make('notes')->label('Notas de Consumo (Opcional)')->columnSpanFull();

                    return $schema;
                })
                ->action(function (array $data, Nicho $record) {
                    $nichoId = $record->id;
                    $consumptionOccurred = false;
                    $detailsToCreate = [];
                    $inventoryUpdates = [];
                    $transaction = null;
                    try {
                        DB::transaction(function () use ($data, $nichoId, &$consumptionOccurred, &$detailsToCreate, &$inventoryUpdates, &$transaction) {
                            $currentInventory = \App\Models\NichoProduct::where('nicho_id', $nichoId)->where('quantity', '>', 0)->lockForUpdate()->get()->keyBy('product_id');
                            foreach (($data['remaining_quantity'] ?? []) as $productId => $remainingQty) {
                                if (filled($remainingQty) && isset($currentInventory[$productId])) {
                                    $remainingQty = (float) $remainingQty;
                                    $inventoryItem = $currentInventory[$productId];
                                    $currentQty = (float) $inventoryItem->quantity;
                                    $consumedQty = $currentQty - $remainingQty;
                                    if ($consumedQty > 0.001 && $remainingQty >= 0) {
                                        $detailsToCreate[] = [
                                            'product_id' => $productId,
                                            'quantity_change' => -$consumedQty,
                                        ];
                                        $inventoryUpdates[$productId] = $remainingQty;
                                        $consumptionOccurred = true;
                                    }
                                }
                            }
                            if ($consumptionOccurred) {
                                $transaction = \App\Models\Transaction::create([
                                    'nicho_id' => $nichoId,
                                    'type' => 'consumption',
                                    'notes' => $data['notes'] ?? null,
                                    'transaction_date' => now(),
                                ]);
                                $transaction->details()->createMany($detailsToCreate);
                                foreach ($inventoryUpdates as $prodId => $finalQty) {
                                    \App\Models\NichoProduct::where('nicho_id', $nichoId)->where('product_id', $prodId)->update(['quantity' => $finalQty]);
                                }
                                // TODO: Trigger email notification using $record->customer and $transaction data.
                            }
                        });
                        if ($consumptionOccurred && $transaction) {
                            Notification::make()
                                ->title('Consumo Registrado')
                                ->icon('heroicon-o-check-circle')
                                ->body("Inventario actualizado para el Nicho '{$record->identifier}'. ID de Transacción: {$transaction->id}. El cliente será notificado.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No se Registró Consumo')
                                ->icon('heroicon-o-information-circle')
                                ->body("No se realizaron cambios. O no se ingresaron cantidades finales o coincidían con el inventario actual del Nicho '{$record->identifier}'.")
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al Registrar Consumo')
                            ->icon('heroicon-o-x-circle')
                            ->body('Ocurrió un error al registrar el consumo. Por favor intente nuevamente o contacte soporte.')
                            ->danger()
                            ->send();
                        throw $e;
                    }
                }),
            Action::make('printNicho')
                ->label('Imprimir Información')
                ->icon('heroicon-o-printer')
                ->color('success')  // Cambio de 'secondary' a 'success' para mejor visibilidad
                ->url(fn ($record) => route('nichos.print', $record))
                ->openUrlInNewTab()
                ->modal(false),
        ];
    }
}
