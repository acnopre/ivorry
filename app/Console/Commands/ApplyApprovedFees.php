<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\Procedure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyApprovedFees extends Command
{
    protected $signature   = 'fees:apply-approved';
    protected $description = 'Apply approved service fees whose effective date has arrived';

    public function handle(): void
    {
        $rows = DB::table('clinic_services')
            ->whereNotNull('new_fee')
            ->whereNotNull('approved_at')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No fees to apply.');
            return;
        }

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                $oldFee    = $row->fee;
                $newFee    = $row->new_fee;
                $difference = $newFee - $oldFee;

                // Apply fee
                DB::table('clinic_services')
                    ->where('clinic_id', $row->clinic_id)
                    ->where('service_id', $row->service_id)
                    ->update([
                        'old_fee'     => $oldFee,
                        'fee'         => $newFee,
                        'new_fee'     => null,
                        'approved_at' => null,
                        'effective_date' => null,
                    ]);

                // Create adjustment procedures only for processed claims
                // whose availment_date >= effective_date (they were processed under the old fee)
                if ($difference != 0) {
                    $processedQuery = Procedure::where('clinic_id', $row->clinic_id)
                        ->where('service_id', $row->service_id)
                        ->where('status', Procedure::STATUS_PROCESSED)
                        ->whereDate('availment_date', '>=', $row->effective_date);

                    foreach ($processedQuery->get() as $processed) {
                        Procedure::create([
                            'member_id'       => $processed->member_id,
                            'clinic_id'       => $row->clinic_id,
                            'service_id'      => $row->service_id,
                            'availment_date'  => $processed->availment_date,
                            'status'          => Procedure::STATUS_VALID,
                            'remarks'         => 'Service fee adjustment after approval',
                            'applied_fee'     => $difference,
                            'is_fee_adjusted' => true,
                            'adc_number_from' => $processed->adc_number,
                        ]);
                    }
                }

                // Reset clinic fee_approval if no more pending/approved fees
                $stillPending = DB::table('clinic_services')
                    ->where('clinic_id', $row->clinic_id)
                    ->whereNotNull('new_fee')
                    ->exists();

                if (!$stillPending) {
                    Clinic::where('id', $row->clinic_id)->update(['fee_approval' => null]);
                }
            });

            $this->info("Applied fee for clinic_id={$row->clinic_id} service_id={$row->service_id}: {$row->fee} → {$row->new_fee}");
        }
    }
}
