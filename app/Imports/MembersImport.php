<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use App\Models\ImportLog;
use App\Models\ImportLogItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MembersImport implements ToModel, WithChunkReading, WithHeadingRow
{
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

        try {
            $user = User::create([
                'name'     => $row['first_name'] . ' ' . $row['last_name'],
                'email'    => $row['email'] ?? null,
                'password' => bcrypt('password'),
            ]);

            $user->assignRole('Member');

            $member = new Member([
                'user_id'       => $user->id,
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
            ]);

            $this->logSuccess($row);
            $this->imported++;
            return $member;
        } catch (\Exception $e) {
            $this->logError($row, $e->getMessage());
            return null;
        }
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
}
