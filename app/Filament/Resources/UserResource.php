<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // --- NAVIGATION PROPERTIES ---
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 35;
    // --- END NAVIGATION PROPERTIES ---

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->required(fn (string $context) => $context === 'create')
                    ->confirmed()
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->label('Confirm Password')
                    ->required(fn (string $context) => $context === 'create')
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->dehydrated(false),
                Forms\Components\Select::make('restaurants')
                    ->multiple()
                    ->relationship('restaurants', 'name')
                    ->preload()
                    ->searchable()
                    ->label('Assigned Restaurants')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('restaurants.name')
                    ->label('Restaurants')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
