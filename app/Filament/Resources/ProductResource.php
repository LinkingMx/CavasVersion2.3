<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // --- NAVIGATION PROPERTIES ---
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';
    // --- END NAVIGATION PROPERTIES ---

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'products', column: 'name', ignoreRecord: true),
                Forms\Components\TextInput::make('external_sku')
                    ->label('SKU Externo')
                    ->nullable()
                    ->maxLength(255)
                    ->unique(table: 'products', column: 'external_sku', ignoreRecord: true),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('external_sku')
                    ->label('SKU Externo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('MXN', locale: 'es_MX')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
