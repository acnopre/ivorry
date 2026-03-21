<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ErdDiagram extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'ERD Diagram';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.erd-diagram';

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view');
    }
}
