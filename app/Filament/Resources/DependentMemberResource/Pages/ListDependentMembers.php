<?php

namespace App\Filament\Resources\DependentMemberResource\Pages;

use App\Filament\Resources\DependentMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDependentMembers extends ListRecords
{
    protected static string $resource = DependentMemberResource::class;
  
    public function getTitle(): string
    {
        return 'Dependent Members';
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
