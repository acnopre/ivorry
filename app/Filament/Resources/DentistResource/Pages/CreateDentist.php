<?php

namespace App\Filament\Resources\DentistResource\Pages;

use App\Filament\Resources\DentistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDentist extends CreateRecord
{
    protected static string $resource = DentistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
