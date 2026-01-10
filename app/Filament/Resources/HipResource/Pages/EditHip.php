<?php

namespace App\Filament\Resources\HipResource\Pages;

use App\Filament\Resources\HipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHip extends EditRecord
{
    protected static string $resource = HipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
