<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Filament\Widgets\AccountStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($filters = request('tableFilter')) {
            foreach (explode(',', $filters) as $filter) {
                [$column, $value] = explode(':', $filter, 2);
                $this->tableFilters[$column]['values'] = [$value];
            }
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // AccountStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
