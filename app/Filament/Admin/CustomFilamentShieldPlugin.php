<?php

namespace App\Filament\Admin;

use App\Filament\Admin\Resources\RoleResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin as FilamentShieldPluginBase;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Panel;

class CustomFilamentShieldPlugin extends FilamentShieldPluginBase
{
    public function register(Panel $panel): void
    {
        if (! Utils::isResourcePublished($panel)) {
            $panel->resources([
                RoleResource::class,
            ]);
        }
    }
}
