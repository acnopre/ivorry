<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use App\Models\ImportLog;
use App\Models\ImportLogItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MembersImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;
    public $imported = 0;
    public $failed = [];
    public $importLog;
    private $rowNumber = 1;

    public function __construct($filename)
    {
        set_time_limit(0);

        $this->importLog = ImportLog::create([
            'filename' => $filename,
            'disk' => 'public',
            'user_id' => auth()->id(),
            'import_type' => 'member',
        ]);
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row);

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

        DB::beginTransaction();
        try {
            $member = Member::withTrashed()->where('account_id', $account->id)
                ->where('first_name', $row['first_name'])
                ->where('last_name', $row['last_name'])
                ->first();

            if ($member) {
                if ($member->trashed()) {
                    $member->restore();
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
                    $updateData = ['status' => $row['status']];
                    if (strtoupper($account->plan_type) === 'SHARED' && strtoupper($row['member_type']) === 'PRINCIPAL') {
                        $cardTaken = Member::withTrashed()
                            ->where('card_number', $row['card_number'])
                            ->where('id', '!=', $member->id)
                            ->exists();
                        if ($cardTaken) {
                            $this->logError($row, 'Card number already assigned to another member in this account');
                            DB::rollBack();
                            return null;
                        }
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
                    'status'        => $row['status'] ?? 'active',
                    'inactive_date' => is_numeric($row['inactive_date']) ? Date::excelToDateTimeObject($row['inactive_date'])->format('Y-m-d') : null,
                    'import_source' => strtoupper($row['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'import_inactive' : 'import_active',
                    'effective_date' => is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : null,
                    'expiration_date' => is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : null,
                    'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    'import_id'     => $this->importLog->id,
                ]);
                $member->save();

                if (strtoupper($row['status'] ?? 'ACTIVE') === 'ACTIVE') {
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
            $this->logSuccess($row, $member->wasRecentlyCreated ? 'Created' : 'Updated');
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

        if (!preg_match('/^[a-zA-Z0-9_\-\/.]([a-zA-Z0-9_\-\/.\s]*[a-zA-Z0-9_\-\/.])?$/', $row['card_number'])) {
            return 'Invalid card_number. Cannot start or end with a space, and only letters, numbers, spaces, hyphens, slashes, and dots are allowed';
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
            $birthdate = is_numeric($row['birthdate']) ? Date::excelToDateTimeObject($row['birthdate']) : new \DateTime($row['birthdate']);
            if ($birthdate > new \DateTime()) {
                return 'Birthdate cannot be in the future';
            }

            if ($birthdate->diff(new \DateTime())->y > 120) {
                return 'Invalid age. Member age exceeds 120 years';
            }
        }

        if (strtoupper($account->plan_type) === 'INDIVIDUAL') {
            $existingMember = Member::withTrashed()->where('card_number', $row['card_number'])->first();
            if ($existingMember && ($existingMember->first_name !== $row['first_name'] || $existingMember->last_name !== $row['last_name'])) {
                return 'Card number already exists in the system';
            }
        } elseif (strtoupper($account->plan_type) === 'SHARED') {
            if (Member::withTrashed()
                ->where('card_number', $row['card_number'])
                ->where('first_name', $row['first_name'])
                ->where('last_name', $row['last_name'])
                ->exists()
            ) {
                return 'Card number already assigned to this member';
            }
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


        // Return actual error message for debugging
        return $message;
    }

    private function logSuccess($row, string $message = 'Created')
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number' => $this->rowNumber,
            'raw_data' => json_encode($row),
            'status' => 'success',
            'message' => $message,
        ]);
        $this->importLog->increment('total_rows');
        $this->importLog->increment('success_rows');
    }

    private function logError($row, $message)
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number' => $this->rowNumber,
            'raw_data' => json_encode($row),
            'status' => 'error',
            'message' => $message,
        ]);
        $this->failed[] = "Row {$this->rowNumber}: {$message}";
        $this->importLog->increment('total_rows');
        $this->importLog->increment('error_rows');
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
        $this->importLog->update([
            'status' => $this->importLog->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }
}
