<?php

namespace App\Filament\Resources\GeneratedSoaResource\Pages;

use App\Filament\Resources\GeneratedSoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeneratedSoa extends EditRecord
{
    protected static string $resource = GeneratedSoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
