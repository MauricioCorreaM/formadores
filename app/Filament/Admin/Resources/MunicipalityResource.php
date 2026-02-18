<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MunicipalityResource\Pages;
use App\Filament\Admin\Resources\MunicipalityResource\RelationManagers;
use App\Models\Municipality;
use App\Support\Search\LikeSearch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Municipios';

    protected static ?string $modelLabel = 'Municipio';

    protected static ?string $pluralModelLabel = 'Municipios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('dane_code')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                    ->maxLength(255),
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('secretaria_id')
                    ->relationship('secretaria', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dane_code')
                    ->label('CODIGO_DANE')
                    ->searchable(query: fn (Builder $query, string $search): Builder => LikeSearch::apply($query, 'dane_code', $search)),
                Tables\Columns\TextColumn::make('name')
                    ->label('NOMBRE')
                    ->searchable(query: fn (Builder $query, string $search): Builder => LikeSearch::apply($query, 'name', $search)),
                Tables\Columns\TextColumn::make('secretaria.name')
                    ->label('SECRETARÍA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('DEPARTAMENTO')
                    ->sortable(),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        if (! $user->hasRole('node_owner')) {
            return $query;
        }

        $primaryNodeId = $user->primary_node_id;
        if (! $primaryNodeId) {
            return $query->whereRaw('1=0');
        }

        return $query->whereHas('schools', fn (Builder $schoolQuery) => $schoolQuery->where('node_id', $primaryNodeId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
        ];
    }
}
