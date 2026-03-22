<?php

namespace App\Filament\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;

class SystemDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'System Documentation';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 98;
    protected static string $view = 'filament.pages.system-documentation';

    public function exportPdf(): mixed
    {
        $pdf = Pdf::loadView('pdf.system-documentation', [
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'system-documentation-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('exportPdf'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view');
    }
}
