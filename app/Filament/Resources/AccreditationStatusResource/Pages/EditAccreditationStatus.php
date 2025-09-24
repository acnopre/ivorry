<?php

namespace App\Filament\Resources\AccreditationStatusResource\Pages;

use App\Filament\Resources\AccreditationStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccreditationStatus extends EditRecord
{
    protected static string $resource = AccreditationStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
