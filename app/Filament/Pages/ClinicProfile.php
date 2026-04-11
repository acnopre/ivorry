<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClinicProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static string $view = 'filament.pages.clinic-profile';
    protected static ?string $navigationLabel = 'Clinic Profile';
    protected static ?string $title = 'Clinic Profile';
    protected static ?string $navigationGroup = 'My Account';

    public function getClinic()
    {
        $clinic = auth()->user()->clinic;
        if ($clinic) {
            $clinic->load(['services', 'dentists.specializations', 'hip']);
        }
        return $clinic;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->clinic && auth()->user()->can('clinic.profile');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->clinic && auth()->user()->can('clinic.profile');
    }
}
