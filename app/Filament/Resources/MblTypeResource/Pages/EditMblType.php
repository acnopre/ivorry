<?php

namespace App\Filament\Resources\MblTypeResource\Pages;

use App\Filament\Resources\MblTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMblType extends EditRecord
{
    protected static string $resource = MblTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
