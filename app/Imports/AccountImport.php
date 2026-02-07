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
    public function __construct(protected ImportLog $log)
    {
        set_time_limit(0);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        $header = $rows->first()->toArray();
        $serviceColumns = array_slice(array_keys($header), 8);
        $services = Service::all()->keyBy('name');
        $accountsToUpsert = [];
        $pivotData = [];

        foreach ($rows as $index => $row) {
            if (empty($row['company_name'])) continue;

            $this->log->increment('total_rows');

            try {
                $accountsToUpsert[] = [
                    'company_name' => $row['company_name'],
                    'policy_code' => $row['policy_code'],
                    'hip' => $row['hip'],
                    'card_used' => $row['card_used'],
                    'effective_date' => $this->transformDate($row['effective_date']),
                    'expiration_date' => $this->transformDate($row['expiration_date']),
                    'plan_type' => $row['plan_type'],
                    'coverage_period_type' => $row['coverage_type'],
                ];

                ImportLogItem::create([
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 2,
                    'raw_data' => json_encode($row),
                    'status' => 'success',
                ]);

                $this->log->increment('success_rows');
            } catch (\Throwable $e) {
                ImportLogItem::create([
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 2,
                    'raw_data' => json_encode($row),
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
                $this->log->increment('error_rows');
            }
        }

        DB::transaction(function () use ($accountsToUpsert, $rows, $serviceColumns, $services) {
            Account::upsert($accountsToUpsert, ['company_name', 'policy_code'], [
                'hip',
                'card_used',
                'effective_date',
                'expiration_date',
                'plan_type',
                'coverage_period_type'
            ]);

            $accountMap = Account::whereIn('company_name', collect($accountsToUpsert)->pluck('company_name'))
                ->whereIn('policy_code', collect($accountsToUpsert)->pluck('policy_code'))
                ->get()
                ->keyBy(fn($a) => $a->company_name);

            $pivotData = [];
            foreach ($rows as $row) {
                if (empty($row['company_name'])) continue;

                $accountKey = $row['company_name'];
                $accountId = $accountMap[$accountKey]['id'] ?? null;
                if (!$accountId) continue;

                foreach ($serviceColumns as $serviceName) {
                    $quantity = $row[$serviceName] ?? 0;
                    $service = $services->firstWhere('slug', $serviceName);

                    if (isset($service)) {
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
            if (!empty($pivotData)) {
                $accountIds = collect($pivotData)->pluck('account_id')->unique();
                DB::table('account_service')->whereIn('account_id', $accountIds)->delete();
                DB::table('account_service')->insert($pivotData);
            }



            $this->log->update([
                'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
            ]);
        });
    }

    private function transformDate($value)
    {
        try {
            return is_numeric($value) ? Date::excelToDateTimeObject($value)->format('Y-m-d') : $value;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
