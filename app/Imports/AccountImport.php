<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\AccountAmendment;
use App\Models\AccountRenewal;
use App\Models\AccountService;
use App\Models\ImportLog;
use App\Models\ImportLogItem;
use App\Models\Service;
use App\Services\AccountEndorsementService;
use App\Services\MblBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AccountImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors, RemembersRowNumber;

    public int $imported   = 0;
    public array $failed   = [];
    public array $duplicates = [];

    private int $successRows   = 0;
    private int $updatedRows   = 0;
    private int $duplicateRows = 0;
    private int $errorRows     = 0;

    private $services;

    public function __construct(
        public ImportLog $log,
        protected ?int $userId = null,
        protected bool $migrationMode = false
    ) {
        set_time_limit(0);
        $this->services = Service::all()->keyBy('slug');
    }

    public function model(array $row)
    {
        $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

        if (empty($row['company_name'])) {
            $this->logError($row, 'Company name is required');
            return null;
        }

        $endorsementType = strtoupper($row['endorsement_type'] ?? 'NEW');

        if ($error = $this->validateAccountRow($row, $endorsementType)) {
            $this->logError($row, $error);
            return null;
        }

        if ($endorsementType === 'RENEWAL') {
            return $this->handleRenewal($row);
        }

        if ($endorsementType === 'AMENDMENT') {
            return $this->handleAmendment($row);
        }

        return $this->handleNew($row);
    }

    private function handleRenewal(array $row)
    {
        $account = $this->findExistingAccount($row);

        if (!$account) {
            $this->logError($row, "Account '{$row['company_name']}' not found for renewal");
            return null;
        }

        DB::beginTransaction();
        try {
            AccountEndorsementService::deletePendingRenewals($account->id);

            $renewal = AccountRenewal::create([
                'account_id'     => $account->id,
                'effective_date' => $this->transformDate($row['effective_date']),
                'expiration_date' => $this->transformDate($row['expiration_date']),
                'requested_by'   => $this->userId,
                'status'         => $this->migrationMode ? 'APPROVED' : 'PENDING',
            ]);

            $account->update([
                'endorsement_type'   => 'RENEWAL',
                'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
            ]);

            $accountServices = AccountService::where('account_id', $account->id)->get();
            AccountEndorsementService::attachServicesToRenewal($renewal, $accountServices);

            DB::commit();
            $this->logSuccess($row, "Renewal submitted for '{$row['company_name']}'");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logError($row, $this->sanitizeErrorMessage($e));
        }

        return null;
    }

    private function handleAmendment(array $row)
    {
        $account = $this->findExistingAccount($row);

        if (!$account) {
            $this->logError($row, "Account '{$row['company_name']}' not found for amendment");
            return null;
        }

        DB::beginTransaction();
        try {
            AccountEndorsementService::deletePendingAmendments($account->id);

            $amendment = AccountAmendment::create([
                'account_id'          => $account->id,
                'company_name'        => $row['company_name'],
                'policy_code'         => $row['policy_code'],
                'hip'                 => $row['hip'] ?? $account->hip,
                'card_used'           => $row['card_used'] ?? $account->card_used,
                'effective_date'      => $this->transformDate($row['effective_date']) ?? $account->effective_date,
                'expiration_date'     => $this->transformDate($row['expiration_date']) ?? $account->expiration_date,
                'endorsement_type'    => 'AMENDMENT',
                'endorsement_status'  => $this->migrationMode ? 'APPROVED' : 'PENDING',
                'coverage_period_type' => $row['coverage_type'] ?? $account->coverage_period_type,
                'mbl_type'            => $row['mbl_type'] ?? $account->mbl_type,
                'mbl_amount'          => $row['mbl_amount'] ?? $account->mbl_amount,
                'remarks'             => $row['remarks'] ?? null,
                'requested_by'        => $this->userId,
            ]);

            $account->update([
                'endorsement_type'   => 'AMENDMENT',
                'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
            ]);

            if ($this->migrationMode && isset($row['mbl_type']) && $account->mbl_type !== $row['mbl_type']) {
                MblBalanceService::handleMblTypeChange(
                    $account->id,
                    $account->mbl_type,
                    $row['mbl_type'],
                    $row['mbl_amount'] ?? null,
                    $this->transformDate($row['effective_date']) ?? $account->effective_date
                );
            }

            $this->attachServicesToAmendment($amendment, $row);

            DB::commit();
            $this->logSuccess($row, "Amendment submitted for '{$row['company_name']}'");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logError($row, $this->sanitizeErrorMessage($e));
        }

        return null;
    }

    private function handleNew(array $row)
    {
        $existing = Account::withTrashed()
            ->where('company_name', $row['company_name'])
            ->where('policy_code', $row['policy_code'])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                DB::beginTransaction();
                try {
                    $existing->restore();
                    $existing->update([
                        'company_name'         => $row['company_name'],
                        'hip'                  => $row['hip'],
                        'card_used'            => $row['card_used'],
                        'effective_date'       => $this->transformDate($row['effective_date']),
                        'expiration_date'      => $this->transformDate($row['expiration_date']),
                        'plan_type'            => $row['plan_type'],
                        'coverage_period_type' => $row['coverage_type'],
                        'mbl_type'             => $row['mbl_type'] ?? $existing->mbl_type,
                        'mbl_amount'           => $row['mbl_amount'] ?? $existing->mbl_amount,
                        'account_status'       => $this->migrationMode ? 'active' : 'inactive',
                        'endorsement_status'   => $this->migrationMode ? 'APPROVED' : 'PENDING',
                        'import_id'            => $this->log->id,
                    ]);

                    AccountService::withTrashed()->where('account_id', $existing->id)->restore();
                    $this->attachServicesToAccount($existing, $row);

                    DB::commit();
                    $this->logSuccess($row, "Account '{$row['company_name']}' restored successfully");
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->logError($row, $this->sanitizeErrorMessage($e));
                }
            } else {
                $this->logDuplicate($row);
            }
            return null;
        }

        DB::beginTransaction();
        try {
            $account = Account::create([
                'company_name'         => $row['company_name'],
                'policy_code'          => $row['policy_code'],
                'hip'                  => $row['hip'],
                'card_used'            => $row['card_used'],
                'effective_date'       => $this->transformDate($row['effective_date']),
                'expiration_date'      => $this->transformDate($row['expiration_date']),
                'plan_type'            => $row['plan_type'],
                'coverage_period_type' => $row['coverage_type'],
                'mbl_type'             => $row['mbl_type'] ?? 'Procedural',
                'mbl_amount'           => $row['mbl_amount'] ?? null,
                'account_status'       => $this->migrationMode ? 'active' : 'inactive',
                'endorsement_status'   => $this->migrationMode ? 'APPROVED' : 'PENDING',
                'created_by'           => $this->userId,
                'import_id'            => $this->log->id,
            ]);

            $this->attachServicesToAccount($account, $row);

            DB::commit();
            $this->imported++;
            $this->logSuccess($row, "Account '{$row['company_name']}' created successfully");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logError($row, $this->sanitizeErrorMessage($e));
        }

        return null;
    }

    private function validateAccountRow(array $row, string $endorsementType): ?string
    {
        if (!in_array($endorsementType, ['NEW', 'RENEWAL', 'AMENDMENT'])) {
            return 'Invalid endorsement type. Accepted values are: NEW, RENEWAL, or AMENDMENT';
        }

        if ($endorsementType === 'RENEWAL') {
            if (empty($row['effective_date']) || empty($row['expiration_date'])) {
                return 'Renewal requires both effective date and expiration date';
            }
            $effectiveDate   = $this->transformDate($row['effective_date']);
            $expirationDate  = $this->transformDate($row['expiration_date']);
            if (!$effectiveDate || !$expirationDate) {
                return 'Invalid date format for renewal dates. Please use a valid date';
            }
            if ($effectiveDate >= $expirationDate) {
                return 'Effective date must be before the expiration date';
            }
            if ($expirationDate < now()->format('Y-m-d')) {
                return 'Expiration date cannot be in the past';
            }
            return null;
        }

        if ($endorsementType === 'AMENDMENT') {
            return null;
        }

        if (empty($row['company_name']) || empty($row['policy_code']) || empty($row['hip']) || empty($row['plan_type']) || empty($row['coverage_type'])) {
            return 'Missing required fields: Company Name, Policy Code, HIP, Plan Type, and Coverage Type are all required';
        }

        if (!in_array(strtoupper($row['plan_type']), ['INDIVIDUAL', 'SHARED'])) {
            return 'Invalid plan type. Accepted values are: INDIVIDUAL or SHARED';
        }

        if (!in_array(strtoupper($row['coverage_type']), ['ACCOUNT', 'MEMBER'])) {
            return 'Invalid coverage type. Accepted values are: ACCOUNT or MEMBER';
        }

        if (!empty($row['mbl_type']) && !in_array($row['mbl_type'], ['Procedural', 'Fixed'])) {
            return 'Invalid MBL type. Accepted values are: Procedural or Fixed';
        }

        if (!empty($row['mbl_type']) && $row['mbl_type'] === 'Fixed' && empty($row['mbl_amount'])) {
            return 'MBL amount is required when MBL type is Fixed';
        }

        if (strtoupper($row['coverage_type']) === 'ACCOUNT' && (empty($row['effective_date']) || empty($row['expiration_date']))) {
            return 'Effective date and expiration date are required when coverage type is ACCOUNT';
        }

        return null;
    }

    private function sanitizeErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Incorrect date value')) {
            return 'Invalid date value in one of the date fields. Please check the format.';
        }
        if (str_contains($message, 'Duplicate entry')) {
            return 'A record with this policy code already exists.';
        }
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'Connection:')) {
            return 'A database error occurred while saving this record. Please contact support if this persists.';
        }

        return $message;
    }

    private function transformDate($value): ?string
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value)
                ? Date::excelToDateTimeObject((float) $value)->format('Y-m-d')
                : $value;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findExistingAccount(array $row): ?Account
    {
        return Account::withTrashed()
            ->where('company_name', $row['company_name'])
            ->where('policy_code', $row['policy_code'])
            ->first();
    }

    private function attachServicesToAccount(Account $account, array $row): void
    {
        $pivotData = [];
        foreach ($this->services as $service) {
            if (isset($row[$service->slug])) {
                $value       = $row[$service->slug];
                $isUnlimited = $service->type === 'basic' || strtolower($value) === 'unlimited';
                $quantity    = $isUnlimited ? null : (is_numeric($value) ? $value : 0);
                $pivotData[$service->id] = [
                    'quantity'         => $quantity,
                    'default_quantity' => $quantity,
                    'is_unlimited'     => $isUnlimited,
                ];
            }
        }
        if (!empty($pivotData)) {
            $account->services()->sync($pivotData);
        }
    }

    private function attachServicesToAmendment(AccountAmendment $amendment, array $row): void
    {
        foreach ($this->services as $service) {
            if (isset($row[$service->slug])) {
                $value       = $row[$service->slug];
                $isUnlimited = $service->type === 'basic' || strtolower($value) === 'unlimited';
                $quantity    = $isUnlimited ? null : (is_numeric($value) ? $value : 0);
                $amendment->services()->create([
                    'service_id'       => $service->id,
                    'quantity'         => $quantity,
                    'default_quantity' => $quantity,
                    'is_unlimited'     => $isUnlimited,
                ]);
            }
        }
    }

    private function cleanRow(array $row): array
    {
        return array_filter(
            $row,
            fn($key) => !is_numeric($key) && $key !== '',
            ARRAY_FILTER_USE_KEY
        );
    }

    private function logSuccess(array $row, string $message): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $this->getRowNumber(),
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'success',
            'message'       => $message,
        ]);
        $this->successRows++;
    }

    private function logDuplicate(array $row): void
    {
        $rowNum = $this->getRowNumber();
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $rowNum,
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'duplicate',
            'message'       => "Account '{$row['company_name']}' with policy code '{$row['policy_code']}' already exists",
        ]);
        $this->duplicates[] = "Row {$rowNum}: duplicate";
        $this->duplicateRows++;
    }

    private function logError(array $row, string $message): void
    {
        $rowNum = $this->getRowNumber();
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $rowNum,
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'error',
            'message'       => $message,
        ]);
        $this->failed[] = "Row {$rowNum}: {$message}";
        $this->errorRows++;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function onError(\Throwable $e): void
    {
        Log::error('Account import error', [
            'row'     => $this->getRowNumber(),
            'message' => $this->sanitizeErrorMessage($e),
        ]);
    }

    public function __destruct()
    {
        $total = $this->successRows + $this->updatedRows + $this->duplicateRows + $this->errorRows;
        $this->log->update([
            'status'         => $this->errorRows > 0 ? 'partial' : 'completed',
            'total_rows'     => $total,
            'success_rows'   => $this->successRows,
            'updated_rows'   => $this->updatedRows,
            'duplicate_rows' => $this->duplicateRows,
            'error_rows'     => $this->errorRows,
        ]);
    }
}
