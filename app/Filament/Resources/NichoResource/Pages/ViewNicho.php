<?php

namespace App\Filament\Resources\NichoResource\Pages;

use App\Filament\Resources\NichoResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\NichoProduct;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use App\Models\Nicho;

class ViewNicho extends ViewRecord
{
    protected static string $resource = NichoResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addBottles')
                ->label('Add Bottles')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalWidth('xl')
                ->form([
                    Forms\Components\Repeater::make('items')
                        ->label('Products to Add')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->options(Product::pluck('name', 'id')->toArray())
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity Added')
                                ->numeric()
                                ->required(), // Remove minValue and step restrictions
                        ])
                        ->columns(2)
                        ->required()
                        ->addActionLabel('Add Another Product'),
                    Forms\Components\TextInput::make('ticket_number')
                        ->label('Ticket Number (Optional)')
                        ->nullable(),
                    Forms\Components\FileUpload::make('ticket_photo_path')
                        ->label('Ticket Photo (Optional)')
                        ->disk('public')
                        ->directory('ticket-photos')
                        ->image()
                        ->nullable(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, $record) {
                    $nichoId = $record->id;
                    $transaction = null;
                    DB::transaction(function () use ($data, $nichoId, &$transaction) {
                        // Handle file upload
                        if (!empty($data['ticket_photo_path'])) {
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
                                    'quantity' => $item['quantity']
                                ]);
                            }
                        }
                    });
                    Notification::make()
                        ->title('Bottles Added Successfully')
                        ->icon('heroicon-o-check-circle')
                        ->body("New items added to Nicho '{$record->identifier}'. Transaction ID: {$transaction->id}")
                        ->success()
                        ->send();
                }),
            Action::make('recordConsumption')
                ->label('Record Consumption')
                ->icon('heroicon-o-minus-circle')
                ->color('warning')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Record Consumption')
                ->form(function (Action $action, Nicho $record): array {
                    $inventoryItems = $record->products()->wherePivot('quantity', '>', 0)->get();
                    $schema = [];
                    $schema[] = Forms\Components\Placeholder::make('info')->content('Enter the FINAL quantity remaining for each consumed item.');
                    if ($inventoryItems->isEmpty()) {
                        $schema[] = Forms\Components\Placeholder::make('empty')->content('There are no items with positive quantity in this nicho to consume.');
                    } else {
                        foreach ($inventoryItems as $item) {
                            $currentQty = $item->pivot->quantity;
                            $schema[] = Forms\Components\TextInput::make('remaining_quantity.' . $item->id)
                                ->label($item->name . ' (Current: ' . number_format($currentQty, 2) . ')')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($currentQty)
                                ->step(0.1)
                                ->nullable()
                                ->placeholder('Enter final quantity (e.g., 0.7)');
                        }
                    }
                    $schema[] = Forms\Components\Textarea::make('notes')->label('Consumption Notes (Optional)')->columnSpanFull();
                    return $schema;
                })
                ->action(function (array $data, Nicho $record) {
                    $nichoId = $record->id;
                    $consumptionOccurred = false;
                    $detailsToCreate = [];
                    $inventoryUpdates = [];
                    $transaction = null;
                    try {
                        DB::transaction(function () use ($data, $nichoId, $record, &$consumptionOccurred, &$detailsToCreate, &$inventoryUpdates, &$transaction) {
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
                                ->title('Consumption Recorded')
                                ->icon('heroicon-o-check-circle')
                                ->body("Inventory updated for Nicho '{$record->identifier}'. Transaction ID: {$transaction->id}. Customer will be notified.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No Consumption Recorded')
                                ->icon('heroicon-o-information-circle')
                                ->body("No changes were made. Either no final quantities were entered or they matched the current inventory for Nicho '{$record->identifier}'.")
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error Recording Consumption')
                            ->icon('heroicon-o-x-circle')
                            ->body('An error occurred while recording the consumption. Please try again or contact support.')
                            ->danger()
                            ->send();
                        throw $e;
                    }
                }),
            Action::make('printNicho')
                ->label('Imprimir Información')
                ->icon('heroicon-o-printer')
                ->color('secondary')
                ->url(fn ($record) => route('nichos.print', $record))
                ->openUrlInNewTab()
                ->modal(false),
        ];
    }
}
