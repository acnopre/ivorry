<?php

namespace App\Services;

class PrinterService
{
    public static function getPrinter(?string $clinicPrinter = null): ?string
    {
        $defaultPrinter = config('printing.printer_name');

        $availablePrinters = self::getAvailablePrinters();

        // Use clinic printer if available
        if ($clinicPrinter && in_array($clinicPrinter, $availablePrinters)) {
            return $clinicPrinter;
        }

        // Use configured default if available
        if ($defaultPrinter && in_array($defaultPrinter, $availablePrinters)) {
            return $defaultPrinter;
        }

        // Fall back to OS default printer
        $osDefault = self::getOsDefaultPrinter();
        if ($osDefault && in_array($osDefault, $availablePrinters)) {
            return $osDefault;
        }

        // Last resort: return first available printer
        return $availablePrinters[0] ?? null;
    }

    public static function getAvailablePrinters(): array
    {
        $lines = [];
        exec('lpstat -p 2>&1', $lines);

        $printers = [];
        $currentPrinter = null;

        foreach ($lines as $line) {
            if (preg_match('/^printer (\S+).*\benabled\b/i', $line, $matches)) {
                $currentPrinter = $matches[1];
                $printers[$currentPrinter] = true;
            } elseif ($currentPrinter && (
                stripos($line, 'offline') !== false ||
                stripos($line, 'looking for printer') !== false ||
                stripos($line, 'unable') !== false
            )) {
                $printers[$currentPrinter] = false;
                $currentPrinter = null;
            } else {
                $currentPrinter = null;
            }
        }

        return array_keys(array_filter($printers));
    }

    public static function isPrinterOnline(string $printerName): bool
    {
        $lines = [];
        exec('lpstat -p ' . escapeshellarg($printerName) . ' 2>&1', $lines);

        $enabled = false;
        foreach ($lines as $line) {
            if (stripos($line, 'offline') !== false) return false;
            if (stripos($line, 'looking for printer') !== false) return false;
            if (stripos($line, 'unable') !== false) return false;
            if (preg_match('/\benabled\b/i', $line)) $enabled = true;
        }

        return $enabled;
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
