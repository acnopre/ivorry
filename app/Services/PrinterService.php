<?php

namespace App\Services;

class PrinterService
{
    /**
     * Get the printer to use
     *
     * @param string|null $clinicPrinter
     * @return string|null
     */
    public static function getPrinter(?string $clinicPrinter = null): ?string
    {
        // Step 1: Default printer
        $defaultPrinter = config('printing.printer_name');

        // Step 2: Get list of available printers from the OS
        $lines = [];
        exec('lpstat -p', $lines);

        $availablePrinters = [];
        foreach ($lines as $line) {
            if (preg_match('/printer (\S+) (is .+)/', $line, $matches)) {
                $name = $matches[1];
                $status = $matches[2];

                if (str_contains($status, 'enabled')) {
                    $availablePrinters[] = $name;
                }
            }
        }

        // Step 3: Use clinic printer if available and enabled
        if ($clinicPrinter && in_array($clinicPrinter, $availablePrinters)) {
            return $clinicPrinter;
        }

        // Step 4: Use default printer if available
        if (in_array($defaultPrinter, $availablePrinters)) {
            return $defaultPrinter;
        }

        // Step 5: No printer available
        return null;
    }
}
