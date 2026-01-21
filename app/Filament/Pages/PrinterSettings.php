<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PrinterSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static string $view = 'filament.pages.printer-settings';
    protected static ?string $navigationGroup = 'Settings';

    public array $printers = [];

    public function mount()
    {
        $this->printers = $this->getPrinters();
    }

    public function getPrinters(): array
    {
        $output = [];
        $status = 0;

        // Run lpstat to get printers and status
        exec('lpstat -p', $lines, $status);

        if ($status === 0) {
            foreach ($lines as $line) {
                // Example line: printer EPSON_L14150_Series is idle. enabled since ...
                if (preg_match('/printer (\S+) (is .+)/', $line, $matches)) {
                    $output[] = [
                        'name' => $matches[1],
                        'status' => $matches[2],
                    ];
                }
            }
        }

        return $output;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('claims.print');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('claims.print');
    }
}
