<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[
            {
                "name":"super_admin",
                "guard_name":"web",
                "permissions":[
                    "view_role",
                    "view_any_role",
                    "create_role",
                    "update_role",
                    "delete_role",
                    "delete_any_role",
                    "force_delete_role",
                    "force_delete_any_role",
                    "restore_role",
                    "restore_any_role",
                    "replicate_role",
                    "reorder_role",
                    "view_campus",
                    "view_any_campus",
                    "create_campus",
                    "update_campus",
                    "restore_campus",
                    "restore_any_campus",
                    "replicate_campus",
                    "reorder_campus",
                    "delete_campus",
                    "delete_any_campus",
                    "force_delete_campus",
                    "force_delete_any_campus",
                    "view_department",
                    "view_any_department",
                    "create_department",
                    "update_department",
                    "restore_department",
                    "restore_any_department",
                    "replicate_department",
                    "reorder_department",
                    "delete_department",
                    "delete_any_department",
                    "force_delete_department",
                    "force_delete_any_department",
                    "view_focalization",
                    "view_any_focalization",
                    "create_focalization",
                    "update_focalization",
                    "restore_focalization",
                    "restore_any_focalization",
                    "replicate_focalization",
                    "reorder_focalization",
                    "delete_focalization",
                    "delete_any_focalization",
                    "force_delete_focalization",
                    "force_delete_any_focalization",
                    "view_municipality",
                    "view_any_municipality",
                    "create_municipality",
                    "update_municipality",
                    "restore_municipality",
                    "restore_any_municipality",
                    "replicate_municipality",
                    "reorder_municipality",
                    "delete_municipality",
                    "delete_any_municipality",
                    "force_delete_municipality",
                    "force_delete_any_municipality",
                    "view_node",
                    "view_any_node",
                    "create_node",
                    "update_node",
                    "restore_node",
                    "restore_any_node",
                    "replicate_node",
                    "reorder_node",
                    "delete_node",
                    "delete_any_node",
                    "force_delete_node",
                    "force_delete_any_node",
                    "view_school",
                    "view_any_school",
                    "create_school",
                    "update_school",
                    "restore_school",
                    "restore_any_school",
                    "replicate_school",
                    "reorder_school",
                    "delete_school",
                    "delete_any_school",
                    "force_delete_school",
                    "force_delete_any_school",
                    "view_secretaria",
                    "view_any_secretaria",
                    "create_secretaria",
                    "update_secretaria",
                    "restore_secretaria",
                    "restore_any_secretaria",
                    "replicate_secretaria",
                    "reorder_secretaria",
                    "delete_secretaria",
                    "delete_any_secretaria",
                    "force_delete_secretaria",
                    "force_delete_any_secretaria",
                    "view_user",
                    "view_any_user",
                    "create_user",
                    "update_user",
                    "restore_user",
                    "restore_any_user",
                    "replicate_user",
                    "reorder_user",
                    "delete_user",
                    "delete_any_user",
                    "force_delete_user",
                    "force_delete_any_user"
                ]
            },
            {
                "name":"node_owner",
                "guard_name":"web",
                "permissions":[
                    "view_campus",
                    "view_any_campus",
                    "view_department",
                    "view_any_department",
                    "view_focalization",
                    "view_any_focalization",
                    "view_municipality",
                    "view_any_municipality",
                    "view_node",
                    "view_any_node",
                    "view_school",
                    "view_any_school",
                    "view_secretaria",
                    "view_any_secretaria",
                    "view_user",
                    "view_any_user",
                    "create_user",
                    "update_user"
                ]
            },
            {
                "name":"teacher",
                "guard_name":"web",
                "permissions":[]
            }
        ]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        $rolePlusPermissions = json_decode($rolesWithPermissions, true);

        if (blank($rolePlusPermissions)) {
            return;
        }

        $roleModel = Utils::getRoleModel();
        $permissionModel = Utils::getPermissionModel();

        static::createMissingRoles($roleModel, $rolePlusPermissions);
        static::createMissingPermissionsFromRoles($permissionModel, $rolePlusPermissions);
        static::syncRolePermissions($roleModel, $permissionModel, $rolePlusPermissions);
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        $permissions = json_decode($directPermissions, true);

        if (blank($permissions)) {
            return;
        }

        $permissionModel = Utils::getPermissionModel();
        static::createMissingPermissions($permissionModel, $permissions);
    }

    private static function createMissingRoles(string $roleModel, array $rolePlusPermissions): void
    {
        $existingRoleNames = $roleModel::pluck('name')->toArray();
        $newRoles = static::filterNewRoles($rolePlusPermissions, $existingRoleNames);

        if (empty($newRoles)) {
            return;
        }

        $roleModel::insert($newRoles);
    }

    private static function filterNewRoles(array $rolePlusPermissions, array $existingRoleNames): array
    {
        $now = now();
        $newRoles = [];

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            if (in_array($rolePlusPermission['name'], $existingRoleNames)) {
                continue;
            }

            $newRoles[] = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $newRoles;
    }

    private static function createMissingPermissionsFromRoles(string $permissionModel, array $rolePlusPermissions): void
    {
        $allPermissionNames = static::extractUniquePermissionNames($rolePlusPermissions);

        if (empty($allPermissionNames)) {
            return;
        }

        $existingPermissionNames = $permissionModel::whereIn('name', $allPermissionNames)
            ->pluck('name')
            ->toArray();

        $newPermissions = static::buildPermissionsToInsert($allPermissionNames, $existingPermissionNames);

        if (empty($newPermissions)) {
            return;
        }

        $permissionModel::insert($newPermissions);
    }

    private static function extractUniquePermissionNames(array $rolePlusPermissions): array
    {
        return collect($rolePlusPermissions)
            ->flatMap(fn ($rp) => $rp['permissions'] ?? [])
            ->unique()
            ->toArray();
    }

    private static function buildPermissionsToInsert(array $allPermissionNames, array $existingPermissionNames, string $guardName = 'web'): array
    {
        $now = now();
        $permissionsToInsert = [];

        foreach ($allPermissionNames as $permissionName) {
            if (in_array($permissionName, $existingPermissionNames)) {
                continue;
            }

            $permissionsToInsert[] = [
                'name' => $permissionName,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $permissionsToInsert;
    }

    private static function syncRolePermissions(string $roleModel, string $permissionModel, array $rolePlusPermissions): void
    {
        $roleNames = array_column($rolePlusPermissions, 'name');
        $roles = $roleModel::whereIn('name', $roleNames)->get()->keyBy('name');

        $permissionNames = static::extractUniquePermissionNames($rolePlusPermissions);
        $permissions = $permissionModel::whereIn('name', $permissionNames)->get()->keyBy('name');

        $roleIds = $roles->pluck('id')->toArray();

        if (empty($roleIds)) {
            return;
        }

        static::clearExistingRolePermissions($roleIds);
        static::insertRolePermissions($rolePlusPermissions, $roles, $permissions);
    }

    private static function clearExistingRolePermissions(array $roleIds): void
    {
        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->delete();
    }

    private static function insertRolePermissions(array $rolePlusPermissions, $roles, $permissions): void
    {
        $roleHasPermissionsData = static::buildRolePermissionPivotData($rolePlusPermissions, $roles, $permissions);

        if (empty($roleHasPermissionsData)) {
            return;
        }

        DB::table('role_has_permissions')->insert($roleHasPermissionsData);
    }

    private static function buildRolePermissionPivotData(array $rolePlusPermissions, $roles, $permissions): array
    {
        $pivotData = [];

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            if (blank($rolePlusPermission['permissions'])) {
                continue;
            }

            $role = $roles->get($rolePlusPermission['name']);

            if (! $role) {
                continue;
            }

            foreach ($rolePlusPermission['permissions'] as $permissionName) {
                $permission = $permissions->get($permissionName);

                if (! $permission) {
                    continue;
                }

                $pivotData[] = [
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ];
            }
        }

        return $pivotData;
    }

    private static function createMissingPermissions(string $permissionModel, array $permissions): void
    {
        $permissionNames = array_column($permissions, 'name');
        $existingPermissionNames = $permissionModel::whereIn('name', $permissionNames)
            ->pluck('name')
            ->toArray();

        $permissionsToInsert = static::buildPermissionsFromArray($permissions, $existingPermissionNames);

        if (empty($permissionsToInsert)) {
            return;
        }

        $permissionModel::insert($permissionsToInsert);
    }

    private static function buildPermissionsFromArray(array $permissions, array $existingPermissionNames): array
    {
        $now = now();
        $permissionsToInsert = [];

        foreach ($permissions as $permission) {
            if (in_array($permission['name'], $existingPermissionNames)) {
                continue;
            }

            $permissionsToInsert[] = [
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $permissionsToInsert;
    }
}
