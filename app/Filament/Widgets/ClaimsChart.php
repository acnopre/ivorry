<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

namespace App\Filament\Widgets;

use App\Models\Claim;
use Filament\Widgets\ChartWidget;

class ClaimsChart extends ChartWidget
{
    protected static ?string $heading = 'Claims per Month';

    protected function getData(): array
    {
        $data = Claim::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return [
            'datasets' => [
                [
                    'label' => 'Claims',
                    'data' => $data->values()->toArray(),
                ],
            ],
            'labels' => $data->keys()
                ->map(fn($m) => date('F', mktime(0, 0, 0, $m, 1)))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // You can choose 'line', 'pie', etc.
    }
}
