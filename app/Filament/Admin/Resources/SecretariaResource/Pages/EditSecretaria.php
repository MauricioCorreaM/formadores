<?php

namespace App\Filament\Admin\Resources\SecretariaResource\Pages;

use App\Filament\Admin\Resources\SecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecretaria extends EditRecord
{
    protected static string $resource = SecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
