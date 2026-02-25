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
        ]);
    }

    public function model(array $row)
    {
        $this->rowNumber++;

        $account = Account::where('company_name', $row['account_name'])->first();

        if (!$account) {
            $this->logError($row, "Account '{$row['account_name']}' not found");
            return null;
        }

        // Validate: status must have value
        if (empty($row['status'])) {
            $this->logError($row, 'Status is required');
            return null;
        }

        // Validate: if account coverage_type == MEMBER, effective_date and expiration_date need value
        if (strtoupper($account->coverage_period_type) === 'MEMBER') {
            if (empty($row['effective_date']) || empty($row['expiration_date'])) {
                $this->logError($row, 'Effective date and expiration date are required when account coverage type is MEMBER');
                return null;
            }
        }

        // Validate: if account plan_type == SHARED, only 1 PRINCIPAL allowed
        if (strtoupper($account->plan_type) === 'SHARED' && strtoupper($row['member_type']) === 'PRINCIPAL') {
            $existingPrincipal = Member::where('account_id', $account->id)
                ->where('member_type', 'PRINCIPAL')
                ->exists();
            
            if ($existingPrincipal) {
                $this->logError($row, 'Account with SHARED plan type can only have 1 PRINCIPAL member');
                return null;
            }
        }

        DB::beginTransaction();
        try {
            $member = Member::where('account_id', $account->id)
                ->where('first_name', $row['first_name'])
                ->where('last_name', $row['last_name'])
                ->first();

            if ($member) {
                $updateData = ['status' => $row['status']];

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
                    'effective_date' => is_numeric($row['effective_date']) ? Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d') : null,
                    'expiration_date' => is_numeric($row['expiration_date']) ? Date::excelToDateTimeObject($row['expiration_date'])->format('Y-m-d') : null,
                    'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                ]);
                $member->save();

                $user = User::create([
                    'name'     => $row['first_name'] . ' ' . $row['last_name'],
                    'email'    => $row['email'] ?? null,
                    'password' => bcrypt('password'),
                ]);

                $user->assignRole('Member');
                $member->update(['user_id' => $user->id]);
            }

            DB::commit();
            $this->logSuccess($row);
            $this->imported++;
            return $member;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError($row, $this->sanitizeErrorMessage($e));
            return null;
        }
    }

    private function sanitizeErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();


        // Return actual error message for debugging
        return $message;
    }

    private function logSuccess($row)
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number' => $this->rowNumber,
            'raw_data' => json_encode($row),
            'status' => 'success',
        ]);
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
}
