<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    // --- NAVIGATION PROPERTIES ---
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'Restaurantes';

    protected static ?string $modelLabel = 'restaurante';

    protected static ?string $pluralModelLabel = 'restaurantes';
    // --- END NAVIGATION PROPERTIES ---

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('prefix')
                    ->label('Prefijo')
                    ->maxLength(8)
                    ->nullable()
                    ->placeholder('Ej: REST-ABC o CAFE-DEF')
                    ->helperText('Formato: 3-4 letras + guión + 3 letras (máximo 8 caracteres)')
                    ->rules([
                        'nullable',
                        'regex:/^[A-Za-z]{3,4}-[A-Za-z]{3}$/',
                    ])
                    ->validationMessages([
                        'regex' => 'El prefijo debe tener el formato: 3-4 letras, seguido de un guión, seguido de 3 letras (ej: REST-ABC)',
                    ]),
                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->nullable()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('average_price')
                    ->label('Precio Promedio')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable()
                    ->maxValue(999999.99),
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
                Tables\Columns\TextColumn::make('prefix')
                    ->label('Prefijo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('average_price')
                    ->label('Precio Promedio')
                    ->money('MXN')
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
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
