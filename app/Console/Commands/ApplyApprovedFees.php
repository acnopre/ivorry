<?php

namespace App\Console\Commands;

use App\Models\Clinic;
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
                DB::table('clinic_services')
                    ->where('clinic_id', $row->clinic_id)
                    ->where('service_id', $row->service_id)
                    ->update([
                        'old_fee'        => $row->fee,
                        'fee'            => $row->new_fee,
                        'new_fee'        => null,
                        'approved_at'    => null,
                        'effective_date' => null,
                    ]);

                $stillPending = DB::table('clinic_services')
                    ->where('clinic_id', $row->clinic_id)
                    ->whereNotNull('new_fee')
                    ->exists();

                if (! $stillPending) {
                    Clinic::where('id', $row->clinic_id)->update(['fee_approval' => 'APPROVED']);
                }
            });

            $this->info("Applied fee for clinic_id={$row->clinic_id} service_id={$row->service_id}: {$row->fee} → {$row->new_fee}");
        }
    }
}
