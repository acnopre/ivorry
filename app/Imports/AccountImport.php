<?php

namespace App\Imports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AccountImport implements ToModel
{
    public function model(array $row)
    {
        // Skip header row if present
        if ($row[0] === 'company_name' || $row[0] === null) {
            return null;
        }

        return new Account([
            'company_name'    => $row[0],
            'policy_code'     => $row[1],
            'hip'             => $row[2],
            'card_used'       => $row[3],
            'effective_date'  => $this->transformDate($row[4]),
            'expiration_date' => $this->transformDate($row[5]),
            'endorsement_type' => $row[6],  // must be NEW, RENEWAL, or AMENDMENT
            'status'          => (isset($row[7]) && $row[7] == 1) ? 1 : 0,
        ]);
    }

    private function transformDate($value)
    {
        try {
            return Date::excelToDateTimeObject($value);
        } catch (\Error $e) {
            return $value; // if already a valid date string
        }
    }
}
