<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NichoResource\Pages;
use App\Filament\Resources\NichoResource\RelationManagers;
use App\Models\Nicho;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class NichoResource extends Resource
{
    protected static ?string $model = Nicho::class;

    // --- NAVIGATION PROPERTIES ---
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 1;
    // --- END NAVIGATION PROPERTIES ---

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Restaurant'),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Customer'),
                Forms\Components\TextInput::make('identifier')
                    ->required()
                    ->maxLength(255)
                    ->label('Identifier (e.g., CAVA-001)'),
                Forms\Components\Textarea::make('additional_info')
                    ->nullable()
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->label('Additional Information'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->schema([
                Section::make('Nicho Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('identifier')->label('Identifier')->columnSpan(1),
                        TextEntry::make('restaurant.name')->label('Restaurant')->columnSpan(1),
                        TextEntry::make('customer.name')->label('Customer')->columnSpan(1),
                        TextEntry::make('created_at')->label('Created')->dateTime()->columnSpan(1),
                        TextEntry::make('additional_info')->label('Additional Information')->columnSpanFull(),
                    ]),
                Section::make('Current Inventory')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('products')
                            ->label('Inventory')
                            ->schema([
                                TextEntry::make('name')->label('Product Name')->columnSpan(1),
                                TextEntry::make('pivot.quantity')->label('Quantity')->numeric(decimalPlaces: 2)->columnSpan(1),
                            ])
                            ->columns(2),
                        TextEntry::make('empty_inventory_message')
                            ->default('No products in this nicho yet.')
                            ->hidden(fn ($record) => $record->products->isNotEmpty()),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNichos::route('/'),
            'create' => Pages\CreateNicho::route('/create'),
            'edit' => Pages\EditNicho::route('/{record}/edit'),
            'view' => Pages\ViewNicho::route('/{record}'),
        ];
    }
}
