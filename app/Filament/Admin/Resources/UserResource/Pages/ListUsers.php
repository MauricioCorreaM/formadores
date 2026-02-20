<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user && $user->hasRole('node_owner')) {
            $nodeName = $user->primaryNode?->name ?? 'Sin nodo';
            return 'Formadores - ' . $nodeName;
        }

        return 'Formadores';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('super_admin')) {
            return [];
        }

        return [
            'formadores' => Tab::make('Formadores')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'teacher'))),
            'administradores' => Tab::make('Administradores')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', ['super_admin', 'node_owner']))),
        ];
    }

    public function isCampusRowsView(): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('super_admin')) {
            return false;
        }

        if (($this->activeTab ?? 'formadores') !== 'formadores') {
            return false;
        }

        return ($this->getTableFilterState('view_mode')['value'] ?? 'grouped_by_user') === 'per_campus';
    }

    public function getTableRecordKey(Model $record): string
    {
        if ($this->isCampusRowsView()) {
            $assignmentId = $record->getAttribute('campus_assignment_id');
            if (filled($assignmentId)) {
                return (string) $assignmentId;
            }
        }

        return parent::getTableRecordKey($record);
    }

    protected function resolveTableRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        if ($this->isCampusRowsView()) {
            return $this->getFilteredTableQuery()
                ->where('campus_user.id', (int) $key)
                ->first();
        }

        return parent::resolveTableRecord($key);
    }
}
