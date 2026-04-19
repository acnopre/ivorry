<?php

namespace App\Imports;

use App\Models\Clinic;
use App\Models\Dentist;
use App\Models\User;
use App\Models\ImportLogItem;
use App\Models\ImportLog;
use App\Models\Service;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class ClinicImport implements ToCollection, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

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
        $nonServiceColumns = [
            'clinic_name', 'registered_name', 'clinic_email', 'password',
            'clinic_mobile', 'clinic_landline', 'complete_address', 'street',
            'region_name', 'province_name', 'municipality_name', 'barangay_name',
            'business_type', 'vat_type', 'withholding_tax', 'tax_identification_no',
            'sec_registration_no', 'ptr_no', 'ptr_date_issued', 'other_hmo_accreditation',
            'update_info_1903', 'accreditation_status', 'account_name', 'hip_name',
            'is_branch', 'bank_name', 'bank_branch', 'bank_account_name',
            'bank_account_number', 'account_type', 'owner_first_name', 'owner_last_name',
            'owner_middle_initial', 'owner_prc_license', 'owner_prc_expiration',
            'clinic_staff_name', 'clinic_staff_mobile', 'clinic_staff_viber',
            'clinic_staff_email', 'viber_no', 'alt_address', 'remarks', 'fee_approval',
        ];
        $serviceColumns = array_values(array_diff(array_keys($header), $nonServiceColumns));
        $services = Service::all()->keyBy('slug');

        foreach ($rows as $index => $row) {
            $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row->toArray());

            if (empty($row['clinic_name'])) continue;

            $this->log->increment('total_rows');

            if ($error = $this->validateRow($row)) {
                $this->logError($index, $row, $error);
                continue;
            }

            $existingClinic = Clinic::where('clinic_name', $row['clinic_name'])->first();

            DB::beginTransaction();
            try {
                $regionId = !empty($row['region_name']) ? \App\Models\Region::where('name', $row['region_name'])->value('id') : null;
                $provinceId = !empty($row['province_name']) ? \App\Models\Province::where('name', $row['province_name'])->value('id') : null;
                $municipalityId = !empty($row['municipality_name']) ? \App\Models\Municipality::where('name', $row['municipality_name'])->value('id') : null;
                $barangayId = !empty($row['barangay_name']) ? \App\Models\Barangay::where('name', $row['barangay_name'])->value('id') : null;

                $accountId = null;
                if ($row['accreditation_status'] === 'SPECIFIC ACCOUNT') {
                    if (empty($row['account_name'])) {
                        $this->logError($index, $row, "account_name is required when accreditation_status is 'SPECIFIC ACCOUNT'");
                        continue;
                    }
                    $accountId = \App\Models\Account::where('company_name', $row['account_name'])->value('id');
                    if (!$accountId) {
                        $this->logError($index, $row, "Account '{$row['account_name']}' not found");
                        continue;
                    }
                }

                $hipId = null;
                if ($row['accreditation_status'] === 'SPECIFIC HIP') {
                    if (empty($row['hip_name'])) {
                        $this->logError($index, $row, "hip_name is required when accreditation_status is 'SPECIFIC HIP'");
                        continue;
                    }
                    $hipId = \App\Models\Hip::where('name', $row['hip_name'])->value('id');
                    if (!$hipId) {
                        $this->logError($index, $row, "HIP '{$row['hip_name']}' not found");
                        continue;
                    }
                }

                $clinicData = [
                    'registered_name'         => $row['registered_name'] ?? null,
                    'ptr_no'                  => $row['ptr_no'] ?? null,
                    'ptr_date_issued'         => $this->transformDate($row['ptr_date_issued'] ?? null),
                    'other_hmo_accreditation' => $row['other_hmo_accreditation'] ?? null,
                    'tax_identification_no'   => $row['tax_identification_no'] ?? null,
                    'is_branch'               => $row['is_branch'] ?? false,
                    'complete_address'        => $row['complete_address'] ?? null,
                    'update_info_1903'        => $row['update_info_1903'] ?? null,
                    'business_type'           => $row['business_type'] ?? null,
                    'vat_type'                => $row['vat_type'] ?? null,
                    'withholding_tax'         => $this->normalizeEnumValue($row['withholding_tax'] ?? null, ['ZERO', '2%', '5%', '10%', '15%']),
                    'sec_registration_no'     => $row['sec_registration_no'] ?? null,
                    'street'                  => $row['street'] ?? null,
                    'region_id'               => $regionId,
                    'province_id'             => $provinceId,
                    'municipality_id'         => $municipalityId,
                    'barangay_id'             => $barangayId,
                    'clinic_landline'         => $row['clinic_landline'] ?? null,
                    'clinic_mobile'           => $row['clinic_mobile'] ?? null,
                    'viber_no'                => $row['viber_no'] ?? null,
                    'clinic_email'            => $row['clinic_email'] ?? null,
                    'alt_address'             => $row['alt_address'] ?? null,
                    'clinic_staff_name'       => $row['clinic_staff_name'] ?? null,
                    'clinic_staff_mobile'     => $row['clinic_staff_mobile'] ?? null,
                    'clinic_staff_viber'      => $row['clinic_staff_viber'] ?? null,
                    'clinic_staff_email'      => $row['clinic_staff_email'] ?? null,
                    'bank_account_name'       => $row['bank_account_name'] ?? null,
                    'bank_account_number'     => $row['bank_account_number'] ?? null,
                    'bank_name'               => $row['bank_name'] ?? null,
                    'bank_branch'             => $row['bank_branch'] ?? null,
                    'account_type'            => $row['account_type'] ?? null,
                    'accreditation_status'    => $row['accreditation_status'] ?? 'INACTIVE',
                    'account_id'              => $accountId,
                    'hip_id'                  => $hipId,
                    'fee_approval'            => $row['fee_approval'] ?? 'PENDING',
                    'remarks'                 => $row['remarks'] ?? null,
                ];

                if ($existingClinic) {
                    // Check duplicate: compare all importable fields
                    if ($this->isDuplicate($existingClinic, $clinicData)) {
                        DB::rollBack();
                        $this->logDuplicate($index, $row);
                        continue;
                    }

                    // Updated: exists but data changed
                    $user = User::updateOrCreate(
                        ['email' => $row['clinic_email']],
                        ['name' => $row['clinic_name'], 'password' => bcrypt($row['password'] ?? 'password')]
                    );
                    $user->assignRole('Dentist');

                    $existingClinic->update(array_merge($clinicData, ['user_id' => $user->id]));
                    $this->syncDentist($existingClinic, $row);
                    $this->syncServices($existingClinic, $services, $serviceColumns, $row);

                    DB::commit();
                    $this->logUpdated($index, $row);
                    continue;
                }

                // New clinic
                $user = User::updateOrCreate(
                    ['email' => $row['clinic_email']],
                    ['name' => $row['clinic_name'], 'password' => bcrypt($row['password'] ?? 'password')]
                );
                $user->assignRole('Dentist');

                $clinic = Clinic::create(array_merge($clinicData, ['user_id' => $user->id, 'clinic_name' => $row['clinic_name']]));
                $this->syncDentist($clinic, $row);
                $this->syncServices($clinic, $services, $serviceColumns, $row);

                DB::commit();
                $this->logSuccess($index, $row, 'Created');
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->logError($index, $row, $e->getMessage());
            }
        }

        $this->log->update([
            'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }

    private function isDuplicate(Clinic $clinic, array $incoming): bool
    {
        $fields = [
            'registered_name', 'ptr_no', 'ptr_date_issued', 'other_hmo_accreditation',
            'tax_identification_no', 'complete_address', 'business_type', 'vat_type',
            'withholding_tax', 'sec_registration_no', 'street', 'region_id', 'province_id',
            'municipality_id', 'barangay_id', 'clinic_landline', 'clinic_mobile', 'viber_no',
            'clinic_email', 'alt_address', 'clinic_staff_name', 'clinic_staff_mobile',
            'clinic_staff_viber', 'clinic_staff_email', 'bank_account_name', 'bank_account_number',
            'bank_name', 'bank_branch', 'account_type', 'accreditation_status',
            'account_id', 'hip_id', 'fee_approval',
        ];

        foreach ($fields as $field) {
            if ($clinic->$field != ($incoming[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function validateRow(array $row): ?string
    {
        if (empty($row['clinic_name'])) {
            return 'clinic_name is required';
        }

        if (empty($row['clinic_email'])) {
            return 'clinic_email is required';
        }

        if (!filter_var($row['clinic_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid clinic_email format';
        }

        // Check if email is already taken by a non-clinic user
        $existingUser = \App\Models\User::where('email', $row['clinic_email'])->first();
        if ($existingUser && !$existingUser->hasRole('Dentist')) {
            return "Email '{$row['clinic_email']}' is already used by another user";
        }

        // Check if email is already used by a different clinic (active only)
        $emailTaken = Clinic::where('clinic_email', $row['clinic_email'])
            ->where('clinic_name', '!=', $row['clinic_name'])
            ->exists();

        if ($emailTaken) {
            return "Email '{$row['clinic_email']}' is already assigned to a different clinic";
        }

        return null;
    }

    private function syncDentist(Clinic $clinic, array $row): void
    {
        if (!empty($row['owner_last_name'])) {
            Dentist::updateOrCreate(
                [
                    'clinic_id'  => $clinic->id,
                    'last_name'  => $row['owner_last_name'],
                    'first_name' => $row['owner_first_name'],
                ],
                [
                    'middle_initial'      => $row['owner_middle_initial'] ?? null,
                    'prc_license_number'  => $row['owner_prc_license'] ?? null,
                    'prc_expiration_date' => $this->transformDate($row['owner_prc_expiration'] ?? null),
                    'is_owner'            => true,
                ]
            );
        }
    }

    private function syncServices(Clinic $clinic, $services, array $serviceColumns, array $row): void
    {
        $pivotData = [];
        foreach ($serviceColumns as $serviceName) {
            $fee = $row[$serviceName] ?? 0;
            $service = $services->firstWhere('slug', $serviceName);
            if ($service && $fee > 0) {
                $pivotData[$service->id] = ['fee' => $fee];
            }
        }

        if (!empty($pivotData)) {
            $clinic->services()->sync($pivotData);
        }
    }

    private function logSuccess(int $index, array $row, string $message = 'Created'): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $index + 2,
            'raw_data'      => json_encode($row),
            'status'        => 'success',
            'message'       => $message,
        ]);
        $this->log->increment('success_rows');
    }

    private function logUpdated(int $index, array $row): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $index + 2,
            'raw_data'      => json_encode($row),
            'status'        => 'updated',
            'message'       => 'Updated: existing record with changed data',
        ]);
        $this->log->increment('updated_rows');
    }

    private function logDuplicate(int $index, array $row): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $index + 2,
            'raw_data'      => json_encode($row),
            'status'        => 'duplicate',
            'message'       => 'Duplicate: all data matches existing record',
        ]);
        $this->log->increment('duplicate_rows');
    }

    private function logError(int $index, array $row, string $message): void
    {
        ImportLogItem::create([
            'import_log_id' => $this->log->id,
            'row_number'    => $index + 2,
            'raw_data'      => json_encode($row),
            'status'        => 'error',
            'message'       => $message,
        ]);
        $this->log->increment('error_rows');
    }

    private function normalizeEnumValue($value, array $validOptions)
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            $percentage = ($value * 100) . '%';
            return in_array($percentage, $validOptions) ? $percentage : null;
        }

        return in_array($value, $validOptions) ? $value : null;
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
        \Log::error('Clinic import error', ['message' => $e->getMessage()]);
    }
}
