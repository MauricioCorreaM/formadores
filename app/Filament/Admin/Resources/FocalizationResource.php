<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FocalizationResource\Pages;
use App\Filament\Admin\Resources\FocalizationResource\RelationManagers;
use App\Models\Focalization;
use App\Support\Search\LikeSearch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FocalizationResource extends Resource
{
    protected static ?string $model = Focalization::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Focalizaciones';

    protected static ?string $modelLabel = 'Focalización';

    protected static ?string $pluralModelLabel = 'Focalizaciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => LikeSearch::apply($query, 'name', $search)),
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

        return $query->whereHas('campuses', function (Builder $campusQuery) use ($primaryNodeId): void {
            $campusQuery->whereHas('school', fn (Builder $schoolQuery) => $schoolQuery->where('node_id', $primaryNodeId));
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFocalizations::route('/'),
            'create' => Pages\CreateFocalization::route('/create'),
            'edit' => Pages\EditFocalization::route('/{record}/edit'),
        ];
    }
}
