<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ActivityTimeline;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        if (session('password_updated')) {
            Notification::make()
                ->success()
                ->title('Welcome!')
                ->body('Your password has been updated successfully.')
                ->send();
        }
    }
    public function getWidgets(): array
    {
        return [
            ActivityTimeline::class,
        ];
    }
}
