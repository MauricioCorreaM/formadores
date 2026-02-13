<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CampusResource\Pages;
use App\Filament\Admin\Resources\CampusResource\RelationManagers;
use App\Models\Campus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampusResource extends Resource
{
    protected static ?string $model = Campus::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Sedes';

    protected static ?string $modelLabel = 'Sede';

    protected static ?string $pluralModelLabel = 'Sedes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('dane_code')
                    ->label('Código DANE')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('zone')
                    ->label('Zona')
                    ->options([
                        'urbana' => 'Urbana',
                        'rural' => 'Rural',
                    ])
                    ->required(),
                Forms\Components\Select::make('school_id')
                    ->label('Colegio')
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('focalizations')
                    ->label('Focalizaciones')
                    ->relationship('focalizations', 'name')
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dane_code')
                    ->label('Código DANE')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zone')
                    ->label('Zona')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urbana' => 'success',
                        'rural' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('Colegio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('focalizations.name')
                    ->label('Focalizaciones')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCampuses::route('/'),
            'create' => Pages\CreateCampus::route('/create'),
            'edit' => Pages\EditCampus::route('/{record}/edit'),
        ];
    }
}
