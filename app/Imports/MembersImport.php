<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use App\Models\ImportLog;
use App\Models\ImportLogItem;
use App\Models\MemberService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MembersImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors, RemembersRowNumber;
    public $imported = 0;
    public $duplicates = [];
    public $failed = [];
    public $importLog;

    private int $successRows   = 0;
    private int $updatedRows   = 0;
    private int $duplicateRows = 0;
    private int $errorRows     = 0;

    public function __construct($filename, ?int $userId = null)
    {
        set_time_limit(0);

        $this->importLog = ImportLog::create([
            'filename'    => $filename,
            'disk'        => 'public',
            'user_id'     => $userId,
            'import_type' => 'member',
        ]);
    }

    public function model(array $row)
    {
        $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row);

        if (!empty($row['card_number'])) {
            $row['card_number'] = preg_replace('/[^a-zA-Z0-9]/', '', $row['card_number']);
        }

        if (!empty($row['gender'])) {
            $row['gender'] = match (strtolower(trim($row['gender']))) {
                'm', 'male'   => 'male',
                'f', 'female' => 'female',
                default       => $row['gender'],
            };
        }

        $account = Account::where('company_name', $row['account_name'])->first();

        if (!$account) {
            $this->logError($row, "Account '{$row['account_name']}' not found");
            return null;
        }

        if (strtoupper($account->account_status) !== 'ACTIVE') {
            $this->logError($row, "Account '{$row['account_name']}' is not active");
            return null;
        }

        if ($error = $this->validateRow($row, $account)) {
            $this->logError($row, $error);
            return null;
        }

        // If inactive_date is in the future, treat as ACTIVE for now
        if (!empty($row['inactive_date']) && strtoupper($row['status'] ?? '') === 'INACTIVE') {
            $inactiveDate = is_numeric($row['inactive_date'])
                ? Date::excelToDateTimeObject($row['inactive_date'])
                : new \DateTime($row['inactive_date']);
            if ($inactiveDate > new \DateTime()) {
                $row['status'] = 'ACTIVE';
            }
        }

        // If account effective_date is in the future, force member INACTIVE
        if ($account->effective_date && $account->effective_date->isFuture()) {
            $row['status'] = 'INACTIVE';
        }

        DB::beginTransaction();
        try {
            $restored = false;

            // Check if account has an approved pending renewal
            $pendingRenewal = \App\Models\AccountRenewal::where('account_id', $account->id)
                ->where('status', 'APPROVED_PENDING_EFFECTIVE')
                ->first();

            if (!empty($row['old_card_number'])) {
                $row['old_card_number'] = preg_replace('/[^a-zA-Z0-9]/', '', $row['old_card_number']);
                $member = Member::withTrashed()
                    ->where('account_id', $account->id)
                    ->where('card_number', $row['old_card_number'])
                    ->first();

                if (!$member) {
                    DB::rollBack();
                    $this->logError($row, "No member found with old_card_number '{$row['old_card_number']}' in this account");
                    return null;
                }

                if ($member->first_name !== $row['first_name'] || $member->last_name !== $row['last_name']) {
                    DB::rollBack();
                    $this->logError($row, "Name mismatch: old_card_number '{$row['old_card_number']}' belongs to {$member->first_name} {$member->last_name}, not {$row['first_name']} {$row['last_name']}");
                    return null;
                }
            } else {
                // Match by card_number — for SHARED accounts multiple members share card_number
                // so also match by name to identify the correct member
                $memberQuery = Member::withTrashed()
                    ->where('account_id', $account->id)
                    ->where('card_number', $row['card_number']);

                if (strtoupper($account->plan_type) === 'SHARED') {
                    $memberQuery->where('first_name', $row['first_name'])
                        ->where('last_name', $row['last_name']);
                }

                $member = $memberQuery->first();
            }

            if ($member) {
                if ($member->trashed()) {
                    $member->restore();
                    $restored = true;
                    $member->update([
                        'card_number'    => $row['card_number'],
                        'member_type'    => $row['member_type'],
                        'status'         => $row['status'] ?? $member->status,
                        'inactive_date'  => !empty($row['inactive_date']) && is_numeric($row['inactive_date']) ? Date::excelToDateTimeObject($row['inactive_date'])->format('Y-m-d') : $member->inactive_date,
                        'effective_date' => !empty($row['effective_date']) && is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : $member->effective_date,
                        'expiration_date' => !empty($row['expiration_date']) && is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : $member->expiration_date,
                        'import_id'      => $this->importLog->id,
                    ]);
                } else {
                    $oldData = [
                        'card_number'    => $member->card_number,
                        'member_type'    => $member->member_type,
                        'status'         => $member->status,
                        'inactive_date'  => $member->inactive_date,
                        'effective_date' => $member->effective_date,
                        'expiration_date' => $member->expiration_date,
                        'birthdate'      => $member->birthdate,
                        'gender'         => $member->gender,
                        'email'          => $member->email,
                        'phone'          => $member->phone,
                        'middle_name'    => $member->middle_name,
                        'suffix'         => $member->suffix,
                    ];

                    $incomingBirthdate = !empty($row['birthdate']) && is_numeric($row['birthdate']) ? Date::excelToDateTimeObject($row['birthdate'])->format('Y-m-d') : ($row['birthdate'] ?? null);
                    $incomingInactive = !empty($row['inactive_date']) && is_numeric($row['inactive_date']) ? Date::excelToDateTimeObject($row['inactive_date'])->format('Y-m-d') : null;
                    $incomingEffective = !empty($row['effective_date']) && is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : null;
                    $incomingExpiration = !empty($row['expiration_date']) && is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : null;

                    $isDuplicate = $member->middle_name == ($row['middle_name'] ?? null)
                        && $member->suffix == ($row['suffix'] ?? null)
                        && strtolower($member->member_type) === strtolower($row['member_type'] ?? '')
                        && $member->card_number === $row['card_number']
                        && $member->birthdate == $incomingBirthdate
                        && strtolower($member->gender ?? '') === strtolower($row['gender'] ?? '')
                        && $member->email == ($row['email'] ?? null)
                        && $member->phone == ($row['phone'] ?? null)
                        && $member->address == ($row['address'] ?? null)
                        && strtolower($member->status) === strtolower($row['status'] ?? '')
                        && $member->inactive_date == $incomingInactive
                        && $member->effective_date == $incomingEffective
                        && $member->expiration_date == $incomingExpiration;

                    if ($isDuplicate) {
                        DB::rollBack();
                        $this->logDuplicate($row);
                        return null;
                    }

                    $updateData = [
                        'status'      => $pendingRenewal ? 'INACTIVE' : $row['status'],
                        'middle_name' => $row['middle_name'] ?? null,
                        'suffix'      => $row['suffix'] ?? null,
                        'gender'      => !empty($row['gender']) ? strtolower($row['gender']) : null,
                        'email'       => $row['email'] ?? null,
                        'phone'       => $row['phone'] ?? null,
                        'birthdate'   => !empty($row['birthdate']) && is_numeric($row['birthdate']) && $row['birthdate'] > 0 ? Date::excelToDateTimeObject($row['birthdate'])->format('Y-m-d') : ((!empty($row['birthdate']) && !is_numeric($row['birthdate'])) ? $row['birthdate'] : null),
                        'renewal_id'  => $pendingRenewal?->id ?? $member->renewal_id,
                    ];

                    // Update card_number only when old_card_number is explicitly provided
                    if (!empty($row['old_card_number'])) {
                        $updateData['card_number'] = $row['card_number'];
                    }

                    if (!empty($row['inactive_date'])) {
                        $updateData['inactive_date'] = is_numeric($row['inactive_date']) ? Date::excelToDateTimeObject($row['inactive_date'])->format('Y-m-d') : null;
                    }
                    if (!empty($row['effective_date'])) {
                        $updateData['effective_date'] = is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : null;
                    }
                    if (!empty($row['expiration_date'])) {
                        $updateData['expiration_date'] = is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : null;
                    }

                    $member->update($updateData);

                    // If SHARED plan and PRINCIPAL set to INACTIVE, set all dependents to INACTIVE
                    if (strtoupper($account->plan_type) === 'SHARED' && strtoupper($row['member_type']) === 'PRINCIPAL' && strtoupper($row['status']) === 'INACTIVE') {
                        Member::where('account_id', $account->id)
                            ->where('member_type', 'DEPENDENT')
                            ->update(['status' => 'inactive', 'inactive_date' => $updateData['inactive_date'] ?? now()->format('Y-m-d')]);
                    }
                }
            } else {
                $member = new Member([
                    'account_id'    => $account->id,
                    'first_name'    => $row['first_name'],
                    'last_name'     => $row['last_name'],
                    'middle_name'   => $row['middle_name'],
                    'suffix'        => $row['suffix'],
                    'member_type'   => $row['member_type'],
                    'card_number'   => $row['card_number'],
                    'birthdate'     => is_numeric($row['birthdate']) ? Date::excelToDateTimeObject($row['birthdate'])->format('Y-m-d') : null,
                    'gender'        => $row['gender'],
                    'email'         => $row['email'],
                    'phone'         => $row['phone'],
                    'address'       => $row['address'] ?? null,
                    'status'        => $pendingRenewal ? 'INACTIVE' : ($row['status'] ?? 'active'),
                    'inactive_date' => is_numeric($row['inactive_date']) ? Date::excelToDateTimeObject($row['inactive_date'])->format('Y-m-d') : null,
                    'import_source' => strtoupper($row['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'import_inactive' : 'import_active',
                    'effective_date' => is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : null,
                    'expiration_date' => is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : null,
                    'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    'import_id'     => $this->importLog->id,
                    'renewal_id'    => $pendingRenewal?->id,
                ]);
                $member->save();

                // Initialize family service quantities for SHARED accounts
                if (strtoupper($account->plan_type) === 'SHARED') {
                    MemberService::initializeForFamily($member->card_number, $account->id);
                }

                if (strtoupper($row['status'] ?? 'ACTIVE') === 'ACTIVE' || $pendingRenewal) {
                    $user = User::create([
                        'name'     => $row['first_name'] . ' ' . $row['last_name'],
                        'email'    => $row['email'] ?? null,
                        'password' => bcrypt('password'),
                    ]);

                    $user->assignRole('Member');
                    $member->update(['user_id' => $user->id]);
                }
            }

            DB::commit();
            if ($member->wasRecentlyCreated) {
                $msg = $pendingRenewal
                    ? 'Created (staged for renewal on ' . \Carbon\Carbon::parse($pendingRenewal->effective_date)->format('M d, Y') . ')'
                    : 'Created';
                $this->logSuccess($row, $msg);
            } elseif (isset($restored) && $restored) {
                $this->logSuccess($row, 'Restored');
            } else {
                $msg = $pendingRenewal
                    ? 'Updated (staged for renewal on ' . \Carbon\Carbon::parse($pendingRenewal->effective_date)->format('M d, Y') . ')'
                    : null;
                $this->logUpdated($row, $oldData, $msg);
            }
            $this->imported++;
            return null; // we handle save() manually, prevent Maatwebsite from calling save() again
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError($row, $this->sanitizeErrorMessage($e));
            return null;
        }
    }

    private function validateRow(array $row, Account $account): ?string
    {
        if (empty($row['first_name']) || empty($row['last_name']) || empty($row['member_type']) || empty($row['card_number'])) {
            return 'Required fields: first_name, last_name, member_type, card_number';
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/u', $row['card_number'])) {
            return 'Invalid card_number. Only letters and numbers are allowed (after cleaning, value was empty or invalid)';
        }

        if (!in_array(strtoupper($row['member_type']), ['PRINCIPAL', 'DEPENDENT'])) {
            return 'Invalid member_type. Must be PRINCIPAL or DEPENDENT';
        }

        if (empty($row['status']) || !in_array(strtoupper($row['status']), ['ACTIVE', 'INACTIVE'])) {
            return 'Invalid status. Must be ACTIVE or INACTIVE';
        }

        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }

        if (!empty($row['birthdate'])) {
            try {
                $birthdate = is_numeric($row['birthdate'])
                    ? Date::excelToDateTimeObject($row['birthdate'])
                    : new \DateTime($row['birthdate']);
            } catch (\Exception $e) {
                return 'Invalid birthdate. Please use a valid date format';
            }

            if ($birthdate > new \DateTime()) {
                return 'Birthdate cannot be in the future';
            }

            if ($birthdate->diff(new \DateTime())->y > 120) {
                return 'Invalid age. Member age exceeds 120 years';
            }
        }

        // When old_card_number is provided, validate the new card_number isn't taken by someone else
        if (!empty($row['old_card_number'])) {
            $cleanOld = preg_replace('/[^a-zA-Z0-9]/', '', $row['old_card_number']);
            $targetMember = Member::withTrashed()
                ->where('account_id', $account->id)
                ->where('card_number', $cleanOld)
                ->first();

            if (!$targetMember) {
                return "No member found with old_card_number '{$cleanOld}' in this account";
            }

            $cardTaken = Member::withTrashed()
                ->where('card_number', $row['card_number'])
                ->where('id', '!=', $targetMember->id)
                ->exists();

            if ($cardTaken) {
                return "New card_number '{$row['card_number']}' is already assigned to another member";
            }
        } else {
            // Block if card_number belongs to a different account
            $existingMember = Member::withTrashed()->where('card_number', $row['card_number'])->first();
            if ($existingMember && $existingMember->account_id !== $account->id) {
                return 'Card number already exists in a different account';
            }
        }

        if (!empty($row['gender']) && !in_array(strtolower($row['gender']), ['male', 'female'])) {
            return 'Invalid gender. Only Male or Female is accepted';
        }

        if (strtoupper($account->coverage_period_type) === 'MEMBER' && (empty($row['effective_date']) || empty($row['expiration_date']))) {
            return 'Effective date and expiration date are required when account coverage type is MEMBER';
        }

        if (strtoupper($account->plan_type) === 'SHARED' && strtoupper($row['member_type']) === 'PRINCIPAL' && Member::withTrashed()->where('account_id', $account->id)->where('member_type', 'PRINCIPAL')->where('first_name', '!=', $row['first_name'])->where('last_name', '!=', $row['last_name'])->exists()) {
            return 'Account with SHARED plan type can only have 1 PRINCIPAL member';
        }

        if (strtoupper($row['member_type']) === 'DEPENDENT' && !Member::withTrashed()->where('account_id', $account->id)->where('member_type', 'PRINCIPAL')->exists()) {
            return 'Cannot add DEPENDENT member without a PRINCIPAL member in the account';
        }

        return null;
    }

    private function sanitizeErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Incorrect date value')) {
            return 'Invalid date value in one of the date fields. Please check the format.';
        }
        if (str_contains($message, 'Duplicate entry')) {
            return 'A record with this card number already exists.';
        }
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'Connection:')) {
            return 'A database error occurred while saving this record. Please contact support if this persists.';
        }

        return $message;
    }

    private function diffSummary(array $old, array $new): string
    {
        $changes = [];
        $compare = ["card_number", "member_type", "status", "inactive_date", "effective_date", "expiration_date", "birthdate", "gender", "email", "phone", "middle_name", "suffix"];
        $dateFields = ["birthdate", "inactive_date", "effective_date", "expiration_date"];
        $labels = ["card_number" => "Card Number", "member_type" => "Member Type", "status" => "Status", "inactive_date" => "Inactive Date", "effective_date" => "Effective Date", "expiration_date" => "Expiration Date", "birthdate" => "Birthdate", "gender" => "Gender", "email" => "Email", "phone" => "Phone", "middle_name" => "Middle Name", "suffix" => "Suffix"];
        foreach ($compare as $field) {
            $oldVal = (string) ($old[$field] ?? "");
            $newVal = (string) ($new[$field] ?? "");
            if (in_array($field, $dateFields) && is_numeric($newVal) && $newVal !== "") {
                $newVal = Date::excelToDateTimeObject((float) $newVal)->format("Y-m-d");
            }
            if (strtolower($oldVal) !== strtolower($newVal)) {
                $from = $oldVal !== "" ? $oldVal : "empty";
                $to   = $newVal !== "" ? $newVal : "empty";
                $changes[] = $labels[$field] . " changed from '" . $from . "' to '" . $to . "'";
            }
        }
        return $changes ? implode("; ", $changes) : "No changes detected";
    }

    private function cleanRow(array $row): array
    {
        return array_filter(
            $row,
            fn($key) => !is_numeric($key) && $key !== '',
            ARRAY_FILTER_USE_KEY
        );
    }

    private function logDuplicate($row)
    {
        $rowNum = $this->getRowNumber();
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number'    => $rowNum,
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'duplicate',
            'message'       => 'Duplicate: all data matches existing record',
        ]);
        $this->duplicates[] = "Row {$rowNum}: duplicate";
        $this->duplicateRows++;
    }

    private function logUpdated($row, array $oldData = [], ?string $messageOverride = null)
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number'    => $this->getRowNumber(),
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'updated',
            'message'       => $messageOverride ?? ('Updated: ' . $this->diffSummary($oldData, $this->cleanRow($row))),
        ]);
        $this->updatedRows++;
    }

    private function logSuccess($row, string $message = 'Created')
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number'    => $this->getRowNumber(),
            'raw_data'      => $this->cleanRow($row),
            'status'        => 'success',
            'message'       => $message,
        ]);
        $this->successRows++;
    }

    private function logError($row, $message)
    {
        $rowNum = $this->getRowNumber();
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
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

    public function onError(\Throwable $e)
    {
        // Log any uncaught errors and continue
        \Log::error('Import error', [
            'row' => $this->rowNumber,
            'message' => $this->sanitizeErrorMessage($e instanceof \Exception ? $e : new \Exception($e->getMessage())),
        ]);
    }

    public function __destruct()
    {
        $total = $this->successRows + $this->updatedRows + $this->duplicateRows + $this->errorRows;
        $this->importLog->update([
            'status'         => $this->errorRows > 0 ? 'partial' : 'completed',
            'total_rows'     => $total,
            'success_rows'   => $this->successRows,
            'updated_rows'   => $this->updatedRows,
            'duplicate_rows' => $this->duplicateRows,
            'error_rows'     => $this->errorRows,
        ]);
    }
}
