<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\ImportLogItem;
use App\Models\ImportLog;
use App\Models\Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow
{
    public function __construct(
        protected ImportLog $log
    ) {
        $this->log = $log;
    }

    public function chunkSize(): int
    {
        return 1000; // process 1000 rows at a time
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Preload all services and map by name
        $services = Service::all()->keyBy('name');

        // Get service column headers from the first row
        $header = $rows->first()->toArray();
        $serviceColumns = array_slice(array_keys($header), 7);

        foreach ($rows as $index => $row) {
            if (empty($row['company_name'])) {
                continue; // skip empty rows
            }

            $this->log->increment('total_rows');

            try {
                DB::transaction(function () use ($row, $index, $serviceColumns, $services) {

                    // Create or update the account
                    $account = Account::firstOrCreate(
                        [
                            'company_name' => $row['company_name'],
                            'policy_code' => $row['policy_code'],
                        ],
                        [
                            'hip' => $row['hip'],
                            'card_used' => $row['card_used'],
                            'effective_date' => $this->transformDate($row['effective_date']),
                            'expiration_date' => $this->transformDate($row['expiration_date']),
                            'plan_type' => $row['plan_type'],
                        ]
                    );

                    $serviceData = [];

                    foreach ($serviceColumns as $serviceName) {
                        $quantity = $row[$serviceName] ?? 0;

                        if ($quantity > 0 && isset($services[$serviceName])) {
                            $service = $services[$serviceName];

                            $serviceData[$service->id] = [
                                'quantity' => $service->type === 'basic' ? null : $quantity,
                                'default_quantity' => $service->type === 'basic' ? null : $quantity,
                                'is_unlimited' => $service->type === 'basic',
                            ];
                        }
                    }

                    // Sync services without detaching existing ones
                    $account->services()->syncWithoutDetaching($serviceData);

                    ImportLogItem::create([
                        'import_log_id' => $this->log->id,
                        'row_number' => $index + 1,
                        'raw_data' => $row,
                        'status' => 'success',
                    ]);

                    $this->log->increment('success_rows');
                });
            } catch (\Throwable $e) {
                ImportLogItem::create([
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 1,
                    'raw_data' => $row,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);

                $this->log->increment('error_rows');
            }
        }

        // Update log status
        $this->log->update([
            'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }

    private function transformDate($value)
    {
        try {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Error $e) {
            return $value; // fallback if not an Excel date
        }
    }
}
