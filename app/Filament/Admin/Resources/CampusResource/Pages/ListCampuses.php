<?php

namespace App\Filament\Admin\Resources\CampusResource\Pages;

use App\Filament\Admin\Resources\CampusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampuses extends ListRecords
{
    protected static string $resource = CampusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
