<?php

namespace App\Filament\Admin\Resources\NodeResource\Pages;

use App\Filament\Admin\Resources\NodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNode extends EditRecord
{
    protected static string $resource = NodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
