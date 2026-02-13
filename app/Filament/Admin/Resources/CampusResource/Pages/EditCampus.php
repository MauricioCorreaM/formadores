<?php

namespace App\Filament\Admin\Resources\CampusResource\Pages;

use App\Filament\Admin\Resources\CampusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampus extends EditRecord
{
    protected static string $resource = CampusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
