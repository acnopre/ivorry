<?php

namespace App\Filament\Resources\ProcedureResource\Pages;

use App\Filament\Resources\ProcedureResource;
use Filament\Resources\Pages\ListRecords;

class ListProcedures extends ListRecords
{
    protected static string $resource = ProcedureResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($filter = request('tableFilter')) {
            [$column, $value] = explode(':', $filter, 2);
            $this->tableFilters[$column]['values'] = [$value];
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
