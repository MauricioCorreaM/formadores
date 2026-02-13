<?php

namespace App\Filament\Admin\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Illuminate\Support\HtmlString;
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

    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(FilamentShield::getResources())
            ->sortKeys()
            ->map(function (array $entity): Forms\Components\Section {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                        ? FilamentShield::getLocalizedResourceLabel($entity['fqcn'])
                        : $entity['model']
                );

                $permissionsArray = static::getResourcePermissionOptions($entity);
                $selectAllFieldName = static::getSelectAllFieldNameForEntity($entity['resource']);

                $checkboxList = static::getCheckBoxListComponentForResource($entity)
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($permissionsArray, $selectAllFieldName): void {
                        $set($selectAllFieldName, count($state ?? []) === count($permissionsArray));
                    });

                return Forms\Components\Section::make($sectionLabel)
                    ->description(fn () => new HtmlString('<span style="word-break: break-word;">' . Utils::showModelPath($entity['fqcn']) . '</span>'))
                    ->compact()
                    ->schema([
                        Forms\Components\Checkbox::make($selectAllFieldName)
                            ->label(__('filament-shield::filament-shield.field.select_all.name'))
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (bool $state, Forms\Set $set) use ($permissionsArray, $entity): void {
                                $set($entity['resource'], $state ? array_keys($permissionsArray) : []);
                            }),
                        $checkboxList,
                    ])
                    ->columnSpan(static::shield()->getSectionColumnSpan())
                    ->collapsible();
            })
            ->toArray();
    }

    protected static function getSelectAllFieldNameForEntity(string $entityResourceName): string
    {
        return '__select_all__' . $entityResourceName;
    }
}
