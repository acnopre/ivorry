<?php

namespace App\Filament\Resources\UpdateInfo1903TypeResource\Pages;

use App\Filament\Resources\UpdateInfo1903TypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUpdateInfo1903Types extends ListRecords
{
    protected static string $resource = UpdateInfo1903TypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
