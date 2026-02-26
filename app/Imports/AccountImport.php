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
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class AccountImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    protected $userId;

    public function __construct(protected ImportLog $log, ?int $userId = null)
    {
        $this->userId = $userId;
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

        foreach ($rows as $index => $row) {
            if (empty($row['company_name'])) continue;

            $this->log->increment('total_rows');

            if ($error = $this->validateAccountRow($row)) {
                $this->logValidationError($index, $row, $error);
                continue;
            }

            DB::beginTransaction();
            try {
                $account = Account::updateOrCreate(
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
                        'coverage_period_type' => $row['coverage_type'],
                        // 'account_status' => config('app.debug') ? 'active' : ($row['account_status'] ?? null),
                        'created_by' => $this->userId,
                    ]
                );

                $pivotData = [];
                foreach ($serviceColumns as $serviceName) {
                    $quantity = $row[$serviceName] ?? 0;
                    $service = $services->firstWhere('slug', $serviceName);

                    if ($service) {
                        $pivotData[$service->id] = [
                            'quantity' => $service->type === 'basic' ? null : $quantity,
                            'default_quantity' => $service->type === 'basic' ? null : $quantity,
                            'is_unlimited' => $service->type === 'basic',
                        ];
                    }
                }

                if (!empty($pivotData)) {
                    $account->services()->sync($pivotData);
                }

                DB::commit();

                ImportLogItem::create([
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 2,
                    'raw_data' => json_encode($row),
                    'status' => 'success',
                ]);

                $this->log->increment('success_rows');
            } catch (\Throwable $e) {
                DB::rollBack();

                ImportLogItem::create([
                    'import_log_id' => $this->log->id,
                    'row_number' => $index + 2,
                    'raw_data' => json_encode($row),
                    'status' => 'error',
                    'message' => $this->sanitizeErrorMessage($e),
                ]);

                $this->log->increment('error_rows');
            }
        }

        $this->log->update([
            'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }

    private function validateAccountRow(array $row): ?string
    {
        if (empty($row['company_name']) || empty($row['policy_code']) || empty($row['hip']) || empty($row['plan_type']) || empty($row['coverage_type'])) {
            return 'Required fields: company_name, policy_code, hip, plan_type, coverage_type';
        }

        if (!in_array(strtoupper($row['plan_type']), ['INDIVIDUAL', 'SHARED'])) {
            return 'Invalid plan_type. Must be INDIVIDUAL or SHARED';
        }

        if (!in_array(strtoupper($row['coverage_type']), ['ACCOUNT', 'MEMBER'])) {
            return 'Invalid coverage_type. Must be ACCOUNT or MEMBER';
        }

        if (!empty($row['mbl_type']) && !in_array($row['mbl_type'], ['Procedural', 'Fixed'])) {
            return 'Invalid mbl_type. Must be Procedural or Fixed';
        }

        if (!empty($row['mbl_type']) && $row['mbl_type'] === 'Fixed' && empty($row['mbl_amount'])) {
            return 'MBL amount is required when mbl_type is Fixed';
        }

        if (strtoupper($row['coverage_type']) === 'MEMBER' && (!empty($row['effective_date']) || !empty($row['expiration_date']))) {
            return 'Coverage type MEMBER cannot have effective_date or expiration_date';
        }

        if (strtoupper($row['coverage_type']) === 'ACCOUNT' && (empty($row['effective_date']) || empty($row['expiration_date']))) {
            return 'Coverage type ACCOUNT requires effective_date and expiration_date';
        }

        return null;
    }

    private function logValidationError(int $index, array $row, string $message): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number' => $index + 2,
            'raw_data' => json_encode($row),
            'status' => 'error',
            'message' => $message,
        ]);
        $this->log->increment('error_rows');
    }

    private function sanitizeErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        return $message;
    }

    private function transformDate($value)
    {
        try {
            return is_numeric($value) ? Date::excelToDateTimeObject($value)->format('Y-m-d') : $value;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function onError(\Throwable $e)
    {
        \Log::error('Account import error', [
            'message' => $this->sanitizeErrorMessage($e),
        ]);
    }
}
