<?php

namespace App\Filament\Admin\Resources\MunicipalityResource\Pages;

use App\Filament\Admin\Resources\MunicipalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
