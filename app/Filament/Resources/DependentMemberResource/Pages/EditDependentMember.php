<?php

namespace App\Filament\Resources\DependentMemberResource\Pages;

use App\Filament\Resources\DependentMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDependentMember extends EditRecord
{
    protected static string $resource = DependentMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
