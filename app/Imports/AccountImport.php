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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow
{
    public function __construct(protected ImportLog $log) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        Log::info('Starting import chunk with ' . $rows->count() . ' rows');

        if ($rows->isEmpty()) {
            Log::info('Chunk is empty, skipping');
            return;
        }

        $header = $rows->first()->toArray();
        $serviceColumns = array_slice(array_keys($header), 7);
        Log::info('Service columns detected: ' . implode(', ', $serviceColumns));

        // Preload services
        $services = Service::all()->keyBy('name');
        Log::info('Loaded ' . $services->count() . ' services from DB');

        $accountsToUpsert = [];
        $pivotData = [];
        $logItems = [];

        foreach ($rows as $index => $row) {
            if (empty($row['company_name'])) continue;

            $this->log->increment('total_rows');

            try {
                // Prepare account data
                $accountsToUpsert[] = [
                    'company_name' => $row['company_name'],
                    'policy_code' => $row['policy_code'],
                    'hip' => $row['hip'],
                    'card_used' => $row['card_used'],
                    'effective_date' => $this->transformDate($row['effective_date']),
                    'expiration_date' => $this->transformDate($row['expiration_date']),
                    'plan_type' => $row['plan_type'],
                ];

                Log::info('Prepared account for upsert: ' . $row['company_name'] . ' | ' . $row['policy_code']);

                // Log item (pending)
                $logItems[] = [
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 1,
                    'raw_data' => $row,
                    'status' => 'pending',
                ];
            } catch (\Throwable $e) {
                Log::error('Error preparing account on row ' . ($index + 1) . ': ' . $e->getMessage());
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

        DB::transaction(function () use ($accountsToUpsert, $rows, $serviceColumns, $services, $logItems) {
            Log::info('Starting DB transaction for ' . count($accountsToUpsert) . ' accounts');

            // 1️⃣ Bulk upsert accounts
            Account::upsert($accountsToUpsert, ['company_name', 'policy_code'], [
                'hip',
                'card_used',
                'effective_date',
                'expiration_date',
                'plan_type'
            ]);
            Log::info('Accounts upsert completed');

            // 2️⃣ Fetch inserted accounts
            $accountMap = Account::whereIn('company_name', collect($accountsToUpsert)->pluck('company_name'))
                ->whereIn('policy_code', collect($accountsToUpsert)->pluck('policy_code'))
                ->get()
                ->keyBy(fn($a) => $a->company_name . '||' . $a->policy_code);
            Log::info('Fetched ' . count($accountMap) . ' accounts from DB');

            // 3️⃣ Build pivot data for services
            foreach ($rows as $index => $row) {
                if (empty($row['company_name'])) continue;

                $accountKey = $row['company_name'] . '||' . $row['policy_code'];
                $accountId = $accountMap[$accountKey]->id ?? null;

                if (!$accountId) {
                    Log::warning('Account not found in DB for pivot: ' . $accountKey);
                    continue;
                }

                foreach ($serviceColumns as $serviceName) {
                    $quantity = $row[$serviceName] ?? 0;
                    if ($quantity > 0 && isset($services[$serviceName])) {
                        $service = $services[$serviceName];
                        $pivotData[] = [
                            'account_id' => $accountId,
                            'service_id' => $service->id,
                            'quantity' => $service->type === 'basic' ? null : $quantity,
                            'default_quantity' => $service->type === 'basic' ? null : $quantity,
                            'is_unlimited' => $service->type === 'basic',
                        ];
                    }
                }
            }

            // 4️⃣ Bulk insert pivot
            if (!empty($pivotData)) {
                DB::table('account_service')->insertOrIgnore($pivotData);
                Log::info('Inserted ' . count($pivotData) . ' pivot records');
            }

            // 5️⃣ Insert log items as success
            foreach ($logItems as &$log) {
                $log['status'] = 'success';
            }
            ImportLogItem::insert($logItems);
            Log::info('Inserted ' . count($logItems) . ' import log items');

            $this->log->update([
                'success_rows' => count($logItems),
                'status' => 'completed',
            ]);
            Log::info('ImportLog updated as completed');
        });

        Log::info('Finished processing chunk');
    }

    private function transformDate($value)
    {
        try {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning('Failed to transform date: ' . $value . ' | ' . $e->getMessage());
            return $value;
        }
    }
}
