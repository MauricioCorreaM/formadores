<?php

namespace App\Filament\Admin\Resources\FocalizationResource\Pages;

use App\Filament\Admin\Resources\FocalizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFocalization extends EditRecord
{
    protected static string $resource = FocalizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
