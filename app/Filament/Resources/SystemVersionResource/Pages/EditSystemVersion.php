<?php

namespace App\Filament\Resources\SystemVersionResource\Pages;

use App\Filament\Resources\SystemVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemVersion extends EditRecord
{
    protected static string $resource = SystemVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
