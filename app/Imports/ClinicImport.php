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
        $serviceColumns = array_slice(array_keys($header), 14);
        $services = Service::all()->keyBy('slug');

        foreach ($rows as $index => $row) {
            if (empty($row['clinic_name'])) continue;

            $this->log->increment('total_rows');

            DB::beginTransaction();
            try {
                $user = User::updateOrCreate(
                    ['email' => $row['clinic_email']],
                    [
                        'name' => $row['clinic_name'],
                        'password' => bcrypt($row['password'] ?? 'password'),
                    ]
                );

                $user->assignRole('Dentist');

                $regionId = !empty($row['region_name']) ? \App\Models\Region::where('name', $row['region_name'])->value('id') : null;
                $provinceId = !empty($row['province_name']) ? \App\Models\Province::where('name', $row['province_name'])->value('id') : null;
                $municipalityId = !empty($row['municipality_name']) ? \App\Models\Municipality::where('name', $row['municipality_name'])->value('id') : null;
                $barangayId = !empty($row['barangay_name']) ? \App\Models\Barangay::where('name', $row['barangay_name'])->value('id') : null;

                $clinic = Clinic::updateOrCreate(
                    ['clinic_name' => $row['clinic_name']],
                    [
                        'user_id' => $user->id,
                        'registered_name' => $row['registered_name'] ?? null,
                        'ptr_no' => $row['ptr_no'] ?? null,
                        'ptr_date_issued' => $this->transformDate($row['ptr_date_issued'] ?? null),
                        'other_hmo_accreditation' => $row['other_hmo_accreditation'] ?? null,
                        'tax_identification_no' => $row['tax_identification_no'] ?? null,
                        'is_branch' => $row['is_branch'] ?? false,
                        'complete_address' => $row['complete_address'] ?? null,
                        'update_info_1903' => $row['update_info_1903'] ?? null,
                        'business_type' => $row['business_type'] ?? null,
                        'vat_type' => $row['vat_type'] ?? null,
                        'withholding_tax' => $this->normalizeEnumValue($row['withholding_tax'] ?? null, ['ZERO', '2%', '5%', '10%', '15%']),
                        'sec_registration_no' => $row['sec_registration_no'] ?? null,
                        'street' => $row['street'] ?? null,
                        'region_id' => $regionId,
                        'province_id' => $provinceId,
                        'municipality_id' => $municipalityId,
                        'barangay_id' => $barangayId,
                        'clinic_landline' => $row['clinic_landline'] ?? null,
                        'clinic_mobile' => $row['clinic_mobile'] ?? null,
                        'viber_no' => $row['viber_no'] ?? null,
                        'clinic_email' => $row['clinic_email'],
                        'alt_address' => $row['alt_address'] ?? null,
                        'clinic_staff_name' => $row['clinic_staff_name'] ?? null,
                        'clinic_staff_mobile' => $row['clinic_staff_mobile'] ?? null,
                        'clinic_staff_viber' => $row['clinic_staff_viber'] ?? null,
                        'clinic_staff_email' => $row['clinic_staff_email'] ?? null,
                        'bank_account_name' => $row['bank_account_name'] ?? null,
                        'bank_account_number' => $row['bank_account_number'] ?? null,
                        'bank_name' => $row['bank_name'] ?? null,
                        'bank_branch' => $row['bank_branch'] ?? null,
                        'account_type' => $row['account_type'] ?? null,
                        'accreditation_status' => $row['accreditation_status'] ?? 'INACTIVE',
                        'account_id' => $row['account_id'] ?? null,
                        'hip_id' => $row['hip_id'] ?? null,
                        'fee_approval' => $row['fee_approval'] ?? 'PENDING',
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );

                if (!empty($row['owner_last_name'])) {
                    Dentist::updateOrCreate(
                        [
                            'clinic_id' => $clinic->id,
                            'last_name' => $row['owner_last_name'],
                            'first_name' => $row['owner_first_name'],
                        ],
                        [
                            'middle_initial' => $row['owner_middle_initial'] ?? null,
                            'prc_license_number' => $row['owner_prc_license'] ?? null,
                            'prc_expiration_date' => $this->transformDate($row['owner_prc_expiration'] ?? null),
                            'is_owner' => true,
                        ]
                    );
                }

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
                    'message' => $e->getMessage(),
                ]);

                $this->log->increment('error_rows');
            }
        }

        $this->log->update([
            'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
        ]);
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
