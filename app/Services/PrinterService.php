<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PrinterService
{
    public static function getPrinter(?string $clinicPrinter = null): ?string
    {
        $defaultPrinter    = config('printing.printer_name');
        $availablePrinters = self::getAvailablePrinters();

        if ($clinicPrinter && in_array($clinicPrinter, $availablePrinters)) {
            return $clinicPrinter;
        }

        if ($defaultPrinter && in_array($defaultPrinter, $availablePrinters)) {
            return $defaultPrinter;
        }

        $osDefault = self::getOsDefaultPrinter();
        if ($osDefault && in_array($osDefault, $availablePrinters)) {
            return $osDefault;
        }

        return $availablePrinters[0] ?? null;
    }

    public static function getAvailablePrinters(): array
    {
        $lines    = [];
        $printers = [];

        exec('lpstat -p 2>&1', $lines);

        Log::info('PrinterService::getAvailablePrinters', ['output' => $lines]);

        foreach ($lines as $line) {
            // Match: "printer PRINTER_NAME is idle." or "printer PRINTER_NAME enabled since..."
            // Also handles: "printer PRINTER_NAME is idle. enabled since..."
            if (preg_match('/^printer\s+(\S+)/i', $line, $matches)) {
                $name = $matches[1];

                // Exclude if explicitly offline/disabled on same line
                if (
                    stripos($line, 'disabled') !== false ||
                    stripos($line, 'offline')  !== false
                ) {
                    continue;
                }

                $printers[] = $name;
            }
        }

        // Fallback: try lpstat -a which lists accepted printers
        if (empty($printers)) {
            $lines2 = [];
            exec('lpstat -a 2>&1', $lines2);

            Log::info('PrinterService::getAvailablePrinters fallback lpstat -a', ['output' => $lines2]);

            foreach ($lines2 as $line) {
                // "PRINTER_NAME accepting requests since..."
                if (preg_match('/^(\S+)\s+accepting/i', $line, $matches)) {
                    $printers[] = $matches[1];
                }
            }
        }

        return array_unique($printers);
    }

    public static function isPrinterOnline(string $printerName): bool
    {
        $lines = [];
        exec('lpstat -p ' . escapeshellarg($printerName) . ' 2>&1', $lines);

        Log::info('PrinterService::isPrinterOnline', ['printer' => $printerName, 'output' => $lines]);

        foreach ($lines as $line) {
            if (stripos($line, 'offline')   !== false) return false;
            if (stripos($line, 'disabled')  !== false) return false;
            if (stripos($line, 'unable')    !== false) return false;

            // Any of these mean it's reachable
            if (
                stripos($line, 'idle')    !== false ||
                stripos($line, 'enabled') !== false ||
                stripos($line, 'ready')   !== false
            ) {
                return true;
            }
        }

        // If lpstat returned something (printer exists) but no clear status, assume online
        return !empty($lines);
    }

    private static function getOsDefaultPrinter(): ?string
    {
        $output = [];
        exec('lpstat -d 2>&1', $output);

        foreach ($output as $line) {
            if (preg_match('/system default destination:\s*(\S+)/i', $line, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
