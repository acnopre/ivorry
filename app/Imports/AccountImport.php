<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\ImportLogItem;
use App\Models\ImportLog;
use App\Models\Service;
use App\Models\Hip;
use App\Services\MblBalanceService;
use App\Services\AccountEndorsementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\CompleteImportJob;

class AccountImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors, Queueable, InteractsWithQueue;

    protected $userId;
    protected $migrationMode;

    public function __construct(protected ImportLog $log, ?int $userId = null, bool $migrationMode = false)
    {
        $this->userId = $userId;
        $this->migrationMode = $migrationMode;
        set_time_limit(0);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        Log::info("Processing chunk with {$rows->count()} rows for import log ID: {$this->log->id}");

        $services = Service::all()->keyBy('slug');

        foreach ($rows as $index => $row) {
            $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row->toArray());

            if (empty($row['company_name'])) {
                $this->log->increment('total_rows');
                $this->logValidationError($index, $row, 'Company name is required');
                continue;
            }

            $this->log->increment('total_rows');

            $endorsementType = strtoupper($row['endorsement_type'] ?? 'NEW');

            Log::info("Row {$index}: endorsement_type = {$endorsementType}, company = {$row['company_name']}");

            if ($error = $this->validateAccountRow($row, $endorsementType)) {
                $this->logValidationError($index, $row, $error);
                continue;
            }

            // Handle RENEWAL: find existing account and create renewal
            if ($endorsementType === 'RENEWAL') {
                $existingAccount = $this->findExistingAccount($row);

                if (!$existingAccount) {
                    $this->logValidationError($index, $row, 'Account not found for renewal');
                    continue;
                }

                DB::beginTransaction();
                try {
                    AccountEndorsementService::deletePendingRenewals($existingAccount->id);

                    $renewal = \App\Models\AccountRenewal::create([
                        'account_id' => $existingAccount->id,
                        'effective_date' => $this->transformDate($row['effective_date']),
                        'expiration_date' => $this->transformDate($row['expiration_date']),
                        'requested_by' => $this->userId,
                        'status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
                    ]);

                    $existingAccount->update([
                        'endorsement_type' => 'RENEWAL',
                        'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
                    ]);

                    $accountServices = \App\Models\AccountService::where('account_id', $existingAccount->id)->get();
                    AccountEndorsementService::attachServicesToRenewal($renewal, $accountServices);

                    DB::commit();
                    $this->logSuccess($index, $row);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->logValidationError($index, $row, $this->sanitizeErrorMessage($e));
                }
                continue;
            }

            // Handle AMENDMENT: find existing account and create amendment
            if ($endorsementType === 'AMENDMENT') {
                $existingAccount = $this->findExistingAccount($row);

                if (!$existingAccount) {
                    $this->logValidationError($index, $row, 'Account not found for amendment');
                    continue;
                }

                DB::beginTransaction();
                try {
                    AccountEndorsementService::deletePendingAmendments($existingAccount->id);

                    $amendment = \App\Models\AccountAmendment::create([
                        'account_id' => $existingAccount->id,
                        'company_name' => $row['company_name'],
                        'policy_code' => $row['policy_code'],
                        'hip' => $row['hip'] ?? $existingAccount->hip,
                        'card_used' => $row['card_used'] ?? $existingAccount->card_used,
                        'effective_date' => $this->transformDate($row['effective_date']) ?? $existingAccount->effective_date,
                        'expiration_date' => $this->transformDate($row['expiration_date']) ?? $existingAccount->expiration_date,
                        'endorsement_type' => 'AMENDMENT',
                        'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
                        'coverage_period_type' => $row['coverage_type'] ?? $existingAccount->coverage_period_type,
                        'mbl_type' => $row['mbl_type'] ?? $existingAccount->mbl_type,
                        'mbl_amount' => $row['mbl_amount'] ?? $existingAccount->mbl_amount,
                        'remarks' => $row['remarks'] ?? null,
                        'requested_by' => $this->userId,
                    ]);

                    $existingAccount->update([
                        'endorsement_type' => 'AMENDMENT',
                        'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
                    ]);

                    // Handle MBL type change if in migration mode
                    if ($this->migrationMode && isset($row['mbl_type'])) {
                        $newMblType = $row['mbl_type'];
                        if ($existingAccount->mbl_type !== $newMblType) {
                            $effectiveDate = $this->transformDate($row['effective_date']) ?? $existingAccount->effective_date;
                            MblBalanceService::handleMblTypeChange(
                                $existingAccount->id,
                                $existingAccount->mbl_type,
                                $newMblType,
                                $row['mbl_amount'] ?? null,
                                $effectiveDate
                            );
                        }
                    }

                    $this->attachServicesToAmendment($amendment, $services, $row);

                    DB::commit();
                    $this->logSuccess($index, $row);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->logValidationError($index, $row, $this->sanitizeErrorMessage($e));
                }
                continue;
            }

            // Handle NEW: check if account already exists (including soft-deleted)
            $existingAccount = Account::withTrashed()->where('company_name', $row['company_name'])->where('policy_code', $row['policy_code'])->first();

            if ($existingAccount) {
                if ($existingAccount->trashed()) {
                    // Restore the soft-deleted account and update with latest row data
                    DB::beginTransaction();
                    try {
                        $existingAccount->restore();
                        $existingAccount->update([
                            'company_name'        => $row['company_name'],
                            'hip'                 => $row['hip'],
                            'card_used'           => $row['card_used'],
                            'effective_date'      => $this->transformDate($row['effective_date']),
                            'expiration_date'     => $this->transformDate($row['expiration_date']),
                            'plan_type'           => $row['plan_type'],
                            'coverage_period_type'=> $row['coverage_type'],
                            'mbl_type'            => $row['mbl_type'] ?? $existingAccount->mbl_type,
                            'mbl_amount'          => $row['mbl_amount'] ?? $existingAccount->mbl_amount,
                            'account_status'      => $this->migrationMode ? 'active' : 'inactive',
                            'endorsement_status'  => $this->migrationMode ? 'APPROVED' : 'PENDING',
                            'import_id'           => $this->log->id,
                        ]);

                        // Restore and re-sync account services
                        \App\Models\AccountService::withTrashed()->where('account_id', $existingAccount->id)->restore();
                        $this->attachServicesToAccount($existingAccount, $services, $row);

                        DB::commit();
                        $this->logSuccess($index, $row, 'Restored (was soft-deleted)');
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        $this->logValidationError($index, $row, $this->sanitizeErrorMessage($e));
                    }
                } else {
                    ImportLogItem::create([
                        'import_log_id' => $this->log->id,
                        'row_number' => $index + 2,
                        'raw_data' => json_encode($row),
                        'status' => 'skipped',
                        'message' => 'Account already exists',
                    ]);
                    $this->log->increment('skipped_rows');
                }
                continue;
            }

            DB::beginTransaction();
            try {

                $account = Account::create([
                    'company_name' => $row['company_name'],
                    'policy_code' => $row['policy_code'],
                    'hip' => $row['hip'],
                    'card_used' => $row['card_used'],
                    'effective_date' => $this->transformDate($row['effective_date']),
                    'expiration_date' => $this->transformDate($row['expiration_date']),
                    'plan_type' => $row['plan_type'],
                    'coverage_period_type' => $row['coverage_type'],
                    'mbl_type' => $row['mbl_type'] ?? 'Procedural',
                    'mbl_amount' => $row['mbl_amount'] ?? null,
                    'account_status' => $this->migrationMode ? 'active' : 'inactive',
                    'endorsement_status' => $this->migrationMode ? 'APPROVED' : 'PENDING',
                    'created_by' => $this->userId,
                    'import_id' => $this->log->id,
                ]);

                $this->attachServicesToAccount($account, $services, $row);

                DB::commit();
                $this->logSuccess($index, $row);
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

        Log::info("Chunk completed for import log ID: {$this->log->id}. Total processed: {$this->log->total_rows}");

        // Dispatch completion job with delay to ensure all chunks finish
        CompleteImportJob::dispatch($this->log)->delay(now()->addSeconds(10));
    }

    private function validateAccountRow(array $row, string $endorsementType = 'NEW'): ?string
    {
        if (!in_array($endorsementType, ['NEW', 'RENEWAL', 'AMENDMENT'])) {
            return 'Invalid endorsement_type. Must be NEW, RENEWAL, or AMENDMENT';
        }

        if ($endorsementType === 'RENEWAL') {
            if (empty($row['effective_date']) || empty($row['expiration_date'])) {
                return 'Renewal requires effective_date and expiration_date';
            }

            $effectiveDate = $this->transformDate($row['effective_date']);
            $expirationDate = $this->transformDate($row['expiration_date']);

            if (!$effectiveDate || !$expirationDate) {
                return 'Invalid date format for renewal dates';
            }

            if ($effectiveDate >= $expirationDate) {
                return 'Renewal effective_date must be before expiration_date';
            }
            //TODO:: check this if valid for validation
            if ($expirationDate < now()->format('Y-m-d')) {
                return 'Renewal effective_date cannot be in the past';
            }

            return null;
        }

        if ($endorsementType === 'AMENDMENT') {
            return null;
        }

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

    private function findExistingAccount(array $row): ?Account
    {
        return Account::withTrashed()->where('company_name', $row['company_name'])
            ->where('policy_code', $row['policy_code'])
            ->first();
    }

    private function attachServicesToAccount(Account $account, $services, array $row): void
    {
        $pivotData = [];
        foreach ($services as $service) {
            $serviceName = $service->slug;
            if (isset($row[$serviceName])) {
                $value = $row[$serviceName];
                $isUnlimited = $service->type === 'basic' || strtolower($value) === 'unlimited';
                $quantity = $isUnlimited ? null : (is_numeric($value) ? $value : 0);

                $pivotData[$service->id] = [
                    'quantity' => $quantity,
                    'default_quantity' => $quantity,
                    'is_unlimited' => $isUnlimited,
                ];
            }
        }

        if (!empty($pivotData)) {
            $account->services()->sync($pivotData);
        }
    }

    private function attachServicesToAmendment($amendment, $services, array $row): void
    {
        foreach ($services as $service) {
            $serviceName = $service->slug;
            if (isset($row[$serviceName])) {
                $value = $row[$serviceName];
                $isUnlimited = $service->type === 'basic' || strtolower($value) === 'unlimited';
                $quantity = $isUnlimited ? null : (is_numeric($value) ? $value : 0);

                $amendment->services()->create([
                    'service_id' => $service->id,
                    'quantity' => $quantity,
                    'default_quantity' => $quantity,
                    'is_unlimited' => $isUnlimited,
                ]);
            }
        }
    }

    private function logSuccess(int $index, array $row, string $message = 'Imported'): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number' => $index + 2,
            'raw_data' => json_encode($row),
            'status' => 'success',
            'message' => $message,
        ]);
        $this->log->increment('success_rows');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Account import chunk failed', [
            'import_log_id' => $this->log->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->log->update(['status' => 'failed']);

        Notification::make()
            ->title('Accounts import failed')
            ->body($exception->getMessage())
            ->danger()
            ->sendToDatabase(\App\Models\User::find($this->log->user_id));
    }

    public function onError(\Throwable $e)
    {
        \Log::error('Account import error', [
            'message' => $this->sanitizeErrorMessage($e),
        ]);

        $this->log->update(['status' => 'failed']);

        Notification::make()
            ->title('Accounts import failed')
            ->body($this->sanitizeErrorMessage($e))
            ->danger()
            ->sendToDatabase(\App\Models\User::find($this->log->user_id));
    }
}
