<?php

namespace App\Filament\Admin\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use Spatie\Permission\Models\Role;

class RoleResource extends BaseRoleResource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    public static function getModel(): string
    {
        return Role::class;
    }
}
