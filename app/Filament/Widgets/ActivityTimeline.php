<?php

namespace App\Filament\Widgets;

use App\Models\Role;
use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;

class ActivityTimeline extends Widget
{
    protected static string $view = 'filament.widgets.activity-timeline';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT]);
    }

    public function getActivities()
    {
        return Activity::with('causer')
            ->latest()
            ->limit(10)
            ->get();
    }
}
