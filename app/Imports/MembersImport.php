<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MembersImport implements ToModel
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip header row
        if ($row[0] === 'account_name') {
            return null;
        }

        // 1️⃣ Find the Account by name
        $account = Account::where('company_name', $row[0])->first();

        // If account doesn't exist, log and skip this row
        if (!$account) {
            Log::warning("Skipped member import: Account '{$row[0]}' not found.", [
                'first_name' => $row[1],
                'last_name'  => $row[2],
                'row'        => $row
            ]);
            return null;
        }

        // 2️⃣ Create User for this member
        $user = User::create([
            'name'     => $row[1] . ' ' . $row[2],
            'email'    => $row[9] ?? Str::slug($row[1] . $row[2] . rand(100, 999)) . '@example.com',
            'password' => bcrypt('password'), // default password
        ]);

        // 3️⃣ Create Member
        return new Member([
            'user_id'      => $user->id,
            'account_id'   => $account->id,
            'first_name'   => $row[1],
            'last_name'    => $row[2],
            'middle_name'  => $row[3],
            'suffix'       => $row[4],
            'member_type'  => $row[5],
            'card_number'  => $row[6],
            'birthdate'    => is_numeric($row[7])
                ? Date::excelToDateTimeObject($row[7])->format('Y-m-d')
                : $row[7],
            'gender'       => $row[8],
            'email'        => $row[9],
            'phone'        => $row[10],
            'address'      => $row[11] ?? null,
        ]);
    }
}
