<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NichoResource\Pages;
use App\Models\Nicho;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NichoResource extends Resource
{
    protected static ?string $model = Nicho::class;

    // --- NAVIGATION PROPERTIES ---
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Gestión de Inventario';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Nichos';

    protected static ?string $modelLabel = 'nicho';

    protected static ?string $pluralModelLabel = 'nichos';
    // --- END NAVIGATION PROPERTIES ---

    public static function form(Form $form): Form
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $restaurantOptions = [];
        if ($user && method_exists($user, 'restaurants')) {
            $restaurantOptions = $user->restaurants()->pluck('name', 'id')->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->options($restaurantOptions)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Restaurante'),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Cliente'),
                Forms\Components\TextInput::make('identifier')
                    ->required()
                    ->maxLength(255)
                    ->label('Identificador (ej. CAVA-001)'),
                Forms\Components\Textarea::make('additional_info')
                    ->nullable()
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->label('Información Adicional'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && method_exists($user, 'restaurants')) {
                    $restaurantIds = $user->restaurants()->pluck('restaurants.id');
                    $query->whereIn('restaurant_id', $restaurantIds);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->label('Identificador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurante')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant_id')
                    ->label('Restaurante')
                    ->relationship('restaurant', 'name')
                    ->searchable(),
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
                Section::make('Detalles del Nicho')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('identifier')->label('Identificador')->columnSpan(1),
                        TextEntry::make('restaurant.name')->label('Restaurante')->columnSpan(1),
                        TextEntry::make('customer.name')->label('Cliente')->columnSpan(1),
                        TextEntry::make('created_at')->label('Creado')->dateTime()->columnSpan(1),
                        TextEntry::make('additional_info')->label('Información Adicional')->columnSpanFull(),
                    ]),
                Section::make('Inventario Actual')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('nonEmptyProducts')
                            ->label('Inventario')
                            ->schema([
                                TextEntry::make('name')->label('Nombre del Producto')->columnSpan(1),
                                TextEntry::make('pivot.quantity')->label('Cantidad')->numeric(decimalPlaces: 2)->columnSpan(1),
                            ])
                            ->columns(2),
                        TextEntry::make('empty_inventory_message')
                            ->default('Aún no hay productos en este nicho.')
                            ->hidden(fn ($record) => $record->nonEmptyProducts->isNotEmpty()),
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
