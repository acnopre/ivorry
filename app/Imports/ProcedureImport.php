<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Service;
use App\Models\Clinic;
use App\Models\ClinicService;
use App\Models\Procedure;
use App\Models\AccountService;
use App\Models\ImportLog;
use App\Models\ImportLogItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProcedureImport implements ToModel, WithChunkReading, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public $imported = 0;
    public $failed = [];
    public $importLog;
    private $rowNumber = 1;
    private $migrationMode;

    public function __construct($filename, bool $migrationMode = false)
    {
        set_time_limit(0);
        $this->migrationMode = $migrationMode;

        $this->importLog = ImportLog::create([
            'filename' => $filename,
            'disk' => 'public',
            'user_id' => auth()->id(),
            'import_type' => 'procedure',
        ]);
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row);

        if ($error = $this->validateRow($row)) {
            $this->logError($row, $error);
            return null;
        }

        DB::beginTransaction();
        try {
            $member = Member::where('first_name', trim($row['first_name']))
                ->where('last_name', trim($row['last_name']))
                ->where('card_number', trim($row['card_number']))
                ->first();

            if (!$member) {
                throw new \Exception("Member not found");
            }

            $service = Service::where('name', trim($row['service_name']))->first();
            if (!$service) {
                throw new \Exception("Service '{$row['service_name']}' not found");
            }

            $clinic = Clinic::where('clinic_name', trim($row['clinic_name']))->first();
            if (!$clinic) {
                throw new \Exception("Clinic '{$row['clinic_name']}' not found");
            }

            // Validate clinic service exists
            $clinicService = ClinicService::where('clinic_id', $clinic->id)
                ->where('service_id', $service->id)
                ->first();
            
            if (!$clinicService) {
                throw new \Exception("Service '{$row['service_name']}' not available in clinic '{$row['clinic_name']}'");
            }

            $accountService = AccountService::where('account_id', $member->account_id)
                ->where('service_id', $service->id)
                ->first();

            if (!$accountService) {
                throw new \Exception("Service not available in member's account");
            }

            $quantity = (int) $row['quantity'];
            
            // Get applied_fee or fallback to clinic service fee
            $appliedFee = null;
            if (!empty($row['applied_fee'])) {
                $appliedFee = (float) $row['applied_fee'];
            } else {
                $appliedFee = $clinicService->fee ?? 0;
            }

            // Check if procedure already exists
            $availmentDate = is_numeric($row['availment_date']) 
                ? Date::excelToDateTimeObject($row['availment_date'])->format('Y-m-d') 
                : $row['availment_date'];

            $existingProcedure = Procedure::where('member_id', $member->id)
                ->where('service_id', $service->id)
                ->where('clinic_id', $clinic->id)
                ->where('availment_date', $availmentDate)
                ->where('is_migrated', true)
                ->first();

            if ($existingProcedure) {
                throw new \Exception("Procedure already exists for this member on {$availmentDate}");
            }

            // Check MBL availability (Fixed type only)
            if ($member->account->mbl_type === 'Fixed') {
                if ($member->mbl_balance < $appliedFee) {
                    throw new \Exception("Insufficient MBL balance");
                }
            }

            // Check quantity availability
            if (!$accountService->is_unlimited && $accountService->quantity < $quantity) {
                throw new \Exception("Insufficient service quantity");
            }

            // Create procedure
            $procedure = Procedure::create([
                'member_id' => $member->id,
                'service_id' => $service->id,
                'clinic_id' => $clinic->id,
                'availment_date' => $availmentDate,
                'quantity' => $quantity,
                'applied_fee' => $appliedFee,
                'status' => $this->migrationMode ? 'processed' : 'pending',
                'remarks' => $row['remarks'] ?? 'Migrated data',
                'is_migrated' => true,
            ]);

            // Deduct only if migration mode is enabled
            if ($this->migrationMode) {
                // Deduct quantity from account service (always)
                if (!$accountService->is_unlimited) {
                    $accountService->decrement('quantity', $quantity);
                }

                // Deduct applied_fee from member MBL balance (Fixed type only)
                if ($member->account->mbl_type === 'Fixed') {
                    $member->decrement('mbl_balance', $appliedFee);
                }
            }

            DB::commit();
            $this->logSuccess($row);
            $this->imported++;
            return $procedure;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logError($row, $e->getMessage());
            return null;
        }
    }

    private function validateRow(array $row): ?string
    {
        if (empty($row['first_name']) || empty($row['last_name']) || empty($row['card_number']) || empty($row['service_name']) || empty($row['clinic_name']) || empty($row['availment_date']) || empty($row['quantity'])) {
            return 'Required fields: first_name, last_name, card_number, service_name, clinic_name, availment_date, quantity';
        }

        if (!is_numeric($row['quantity']) || $row['quantity'] <= 0) {
            return 'Quantity must be a positive number';
        }
        
        if (!empty($row['applied_fee']) && (!is_numeric($row['applied_fee']) || $row['applied_fee'] < 0)) {
            return 'Applied fee must be a valid number';
        }

        return null;
    }

    private function logSuccess($row)
    {
        ImportLogItem::create([
            'import_log_id' => $this->importLog->id,
            'row_number' => $this->rowNumber,
            'raw_data' => json_encode($row),
            'status' => 'success',
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
        $this->logError([], $e->getMessage());
        \Log::error('Procedure import error', [
            'row' => $this->rowNumber,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public function __destruct()
    {
        $this->importLog->update([
            'status' => $this->importLog->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }
}
