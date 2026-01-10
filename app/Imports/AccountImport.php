<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Service;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AccountImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // First row is header
        $header = $rows->first()->toArray();
        $serviceColumns = array_slice($header, 7); // service names

        foreach ($rows as $index => $row) {
            $row = $row->toArray();

            if ($index === 0 || $row[0] === null) {
                continue; // skip header or empty row
            }

            // Create or get account
            $account = Account::firstOrCreate(
                ['company_name' => $row[0], 'policy_code' => $row[1]],
                [
                    'hip'             => $row[2],
                    'card_used'       => $row[3],
                    'effective_date'  => $this->transformDate($row[4]),
                    'expiration_date' => $this->transformDate($row[5]),
                    'plan_type'       => $row[6],
                ]
            );

            $account->save(); // make sure it has an ID

            // Prepare all services for sync
            $serviceData = [];
            foreach ($serviceColumns as $colIndex => $serviceName) {
                $quantity = $row[$colIndex + 7] ?? 0; // +7 because account info
                if ($quantity > 0) {
                    $service = Service::where('name', $serviceName)->first();
                    if ($service) {
                        $serviceData[$service->id] = [
                            'quantity' => $quantity,
                            'default_quantity' => $quantity,
                            'is_unlimited' => $service->type === 'basic' ? true : false,
                        ];
                    }
                }
            }

            if (!empty($serviceData)) {
                $account->services()->syncWithoutDetaching($serviceData);
            }
        }
    }

    private function transformDate($value)
    {
        try {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Error $e) {
            return $value;
        }
    }
}
