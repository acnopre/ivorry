<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SystemDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'System Documentation';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 98;
    protected static string $view = 'filament.pages.system-documentation';

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view');
    }
}
