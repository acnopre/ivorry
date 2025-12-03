<?php

namespace App\Filament\Resources\UpdateInfo1903TypeResource\Pages;

use App\Filament\Resources\UpdateInfo1903TypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUpdateInfo1903Type extends EditRecord
{
    protected static string $resource = UpdateInfo1903TypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
