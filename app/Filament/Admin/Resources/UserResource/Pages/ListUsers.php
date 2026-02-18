<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

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
}
