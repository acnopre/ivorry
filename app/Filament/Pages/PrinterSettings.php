<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class PrinterSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static string $view = 'filament.pages.printer-settings';
    protected static ?string $navigationGroup = 'Settings';

    public array $printers = [];
    public bool $simulatePrint = false;

    public static function isSimulating(): bool
    {
        return Cache::get('print_simulate', config('app.print_simulate', false));
    }

    public function mount()
    {
        $this->printers      = $this->getPrinters();
        $this->debugOutput   = $this->getDebugOutput();
        $this->simulatePrint = self::isSimulating();
    }

    public function toggleSimulate(): void
    {
        $this->simulatePrint = !$this->simulatePrint;
        Cache::forever('print_simulate', $this->simulatePrint);

        Notification::make()
            ->title($this->simulatePrint ? 'Print Simulation Enabled' : 'Print Simulation Disabled')
            ->body($this->simulatePrint ? 'Claims will be marked as processed without sending to printer.' : 'Claims will be sent to the printer as normal.')
            ->color($this->simulatePrint ? 'warning' : 'success')
            ->send();
    }

    public array $debugOutput = [];

    public function getDebugOutput(): array
    {
        $out = [];

        $lp = [];
        exec('which lp 2>&1', $lp);
        $out['which_lp'] = implode(' ', $lp);

        $lpstat = [];
        exec('which lpstat 2>&1', $lpstat);
        $out['which_lpstat'] = implode(' ', $lpstat);

        $p = [];
        exec('lpstat -p 2>&1', $p);
        $out['lpstat_p'] = $p;

        $a = [];
        exec('lpstat -a 2>&1', $a);
        $out['lpstat_a'] = $a;

        $d = [];
        exec('lpstat -d 2>&1', $d);
        $out['lpstat_d'] = $d;

        $whoami = [];
        exec('whoami 2>&1', $whoami);
        $out['whoami'] = implode(' ', $whoami);

        return $out;
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
                if (preg_match('/printer (\S+) (.+)/', $line, $matches)) {
                    $printerName = $matches[1];
                    $statusText = $matches[2];
                    
                    // Check if printer is actually reachable
                    exec('lpstat -p ' . escapeshellarg($printerName) . ' -l 2>&1', $detailLines);
                    $isConnected = !str_contains(implode(' ', $detailLines), 'Unable to connect');
                    
                    $output[] = [
                        'name' => $printerName,
                        'status' => $statusText,
                        'connected' => $isConnected,
                    ];
                }
            }
        }

        // Sort by connected status (online first)
        usort($output, fn($a, $b) => $b['connected'] <=> $a['connected']);

        return $output;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('claims.print');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('claims.print');
    }
}
