<?php

namespace App\Services;

use App\Models\ClinicService;
use App\Models\ClinicServiceFeeHistory;
use App\Models\Member;
use App\Models\Procedure;
use App\Models\ProcedureUnit;
use App\Models\Service;
use Illuminate\Support\Str;

class ProcedureService
{
    const UNIT_INPUTS = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];

    // -------------------------------------------------------------------------
    // Eligibility
    // -------------------------------------------------------------------------

    public static function getAppliedFee(int $clinicId, int $serviceId, string $availmentDate): float
    {
        $history = ClinicServiceFeeHistory::where('clinic_id', $clinicId)
            ->where('service_id', $serviceId)
            ->where('effective_date', '<=', $availmentDate)
            ->orderByDesc('effective_date')
            ->value('new_fee');

        return (float) ($history ?? ClinicService::where('clinic_id', $clinicId)->where('service_id', $serviceId)->value('fee') ?? 0);
    }

    public static function getMemberEligibilityError(Member $member): ?string
    {
        $today = now()->startOfDay();

        if ($member->status !== 'ACTIVE' || $member->inactive_date !== null) {
            return 'Member is not active';
        }
        if ($member->effective_date && $today->lt(\Carbon\Carbon::parse($member->effective_date)->startOfDay())) {
            return 'Member coverage has not started yet';
        }
        if ($member->expiration_date && $today->gt(\Carbon\Carbon::parse($member->expiration_date)->endOfDay())) {
            return 'Member coverage has expired';
        }
        if (!$member->account) {
            return 'No account found';
        }
        if ($member->account->account_status !== 'active') {
            return 'Account is not active';
        }
        if ($member->account->effective_date && $today->lt(\Carbon\Carbon::parse($member->account->effective_date)->startOfDay())) {
            return 'Account coverage has not started yet';
        }
        if ($member->account->expiration_date && $today->gt(\Carbon\Carbon::parse($member->account->expiration_date)->endOfDay())) {
            return 'Account coverage has expired';
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Business Rule Validation
    // -------------------------------------------------------------------------

    public static function validateBusinessRules(array $data, int $memberId, int $clinicId, bool $isCSR = false): ?string
    {
        $service = Service::find($data['service_id']);
        if (!$service) return 'Service not found';

        $serviceName   = $service->name;
        $availmentDate = $data['availment_date'] ?? null;

        if ($service->type === 'special' && !$isCSR) {
            return 'Please call HPDAI for approval to avail this special service.';
        }

        // Multi-clinic restriction
        if ($availmentDate) {
            $existsInDifferentClinic = Procedure::forMember($memberId)
                ->where('clinic_id', '!=', $clinicId)
                ->where('availment_date', $availmentDate)
                ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                ->exists();

            if ($existsInDifferentClinic) {
                return 'Member cannot have procedures in different clinics on the same date.';
            }
        }

        // Extract unit IDs
        $unitIds  = [];
        $hasUnits = false;
        foreach (self::UNIT_INPUTS as $input) {
            if (!empty($data[$input])) {
                $hasUnits = true;
                $unitIds  = array_merge($unitIds, (array) $data[$input]);
            }
        }

        // Duplicate procedure check
        if ($hasUnits) {
            foreach ($unitIds as $unitId) {
                // Check same service on same unit
                $exists = Procedure::where('service_id', $data['service_id'])
                    ->forMember($memberId)
                    ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                    ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
                    ->exists();

                if ($exists) {
                    return 'This procedure already exists and is currently pending. Please contact HPDAI for assistance.';
                }

                // If unit type is Tooth, check if any other procedure already exists on same tooth same date
                $service = \App\Models\Service::with('unitType')->find($data['service_id']);
                if ($service?->unitType?->name === 'Tooth' && $availmentDate) {
                    $toothConflict = Procedure::forMember($memberId)
                        ->where('service_id', '!=', $data['service_id'])
                        ->where('availment_date', $availmentDate)
                        ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED, Procedure::STATUS_REJECT])
                        ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
                        ->with('service')
                        ->first();

                    if ($toothConflict) {
                        $unit = \App\Models\Unit::find($unitId);
                        return "Tooth {$unit?->name} already has a pending procedure ({$toothConflict->service->name}) on this date.";
                    }
                }
            }
        } else {
            $exists = Procedure::where('service_id', $data['service_id'])
                ->forMember($memberId)
                ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                ->exists();

            if ($exists) {
                return 'This procedure already exists and is currently pending. Please contact HPDAI for assistance.';
            }
        }

        if (!$availmentDate) return null;

        // Max per date
        if ($service->max_per_date) {
            $existingCount = Procedure::forMember($memberId)
                ->where('service_id', $data['service_id'])
                ->where('availment_date', $availmentDate)
                ->whereIn('status', [Procedure::STATUS_PENDING, Procedure::STATUS_SIGN])
                ->count();

            $newCount = 0;
            foreach (self::UNIT_INPUTS as $input) {
                if (!empty($data[$input])) $newCount += count((array) $data[$input]);
            }
            $newCount = max($newCount, 1);

            if (($existingCount + $newCount) > $service->max_per_date) {
                $remaining = max($service->max_per_date - $existingCount, 0);
                return "{$serviceName} is limited to {$service->max_per_date} per date. Already used: {$existingCount}, remaining: {$remaining}.";
            }
        }

        // Exclusive service (Consultation)
        $exclusiveServices = ['Consultation'];
        if (in_array($serviceName, $exclusiveServices)) {
            if ($hasUnits) {
                foreach ($unitIds as $unitId) {
                    if (self::hasOtherProceduresOnUnit($memberId, $clinicId, $availmentDate, $unitId)) {
                        return "{$serviceName} cannot be done with other procedures on the same unit on the same date.";
                    }
                }
            } elseif (self::hasOtherProcedures($memberId, $clinicId, $availmentDate)) {
                return "{$serviceName} cannot be done with other procedures on the same date.";
            }
        } else {
            foreach ($exclusiveServices as $exclusive) {
                if (self::hasProcedureByName($memberId, $clinicId, $availmentDate, $exclusive)) {
                    return "No other procedures can be done on the same date as {$exclusive}.";
                }
                if ($hasUnits) {
                    foreach ($unitIds as $unitId) {
                        if (self::hasProcedureOnUnit($memberId, $clinicId, $availmentDate, $exclusive, $unitId)) {
                            return "No other procedure can be done with extraction on same tooth number.";
                        }
                    }
                }
            }
        }

        // Service pair restrictions
        $restrictions = [
            'Treatment of sores, blisters'            => ['Oral Prophylaxis'],
            'Desensitization of Hypersensitive teeth'  => ['Oral Prophylaxis'],
            'Oral Prophylaxis'                         => ['Treatment of sores, blisters', 'Desensitization of Hypersensitive teeth'],
        ];

        if (isset($restrictions[$serviceName])) {
            foreach ($restrictions[$serviceName] as $conflictService) {
                if (self::hasProcedureByName($memberId, $clinicId, $availmentDate, $conflictService)) {
                    return "{$serviceName} cannot be done with {$conflictService} on the same date.";
                }
            }
        }

        // Tooth-specific validations
        if (!empty($data['tooth'])) {
            foreach ($data['tooth'] as $toothId) {
                if ($serviceName === 'Temporary fillings' && self::hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return 'Temporary fillings cannot be done on the same tooth as permanent filling on the same date.';
                }
                if (in_array($serviceName, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    if (self::hasToothProcedure($memberId, $availmentDate, $toothId, ['Temporary fillings'])) {
                        return 'Permanent filling cannot be done on the same tooth as temporary fillings on the same date.';
                    }
                    if (self::hasToothProcedure($memberId, $availmentDate, $toothId, ['Desensitization of Hypersensitive teeth'])) {
                        return 'Permanent filling cannot be done on the same tooth with desensitization on the same date.';
                    }
                }
                if ($serviceName === 'Desensitization of Hypersensitive teeth' && self::hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return 'Desensitization cannot be done on the same tooth with permanent filling on the same date.';
                }
                if ($serviceName === 'Simple tooth extraction' && self::hasToothProcedure($memberId, null, $toothId, ['Simple tooth extraction'])) {
                    return 'Simple tooth extraction can only be done once per tooth.';
                }
                // Block extraction if other procedures already exist on same tooth same date
                if ($serviceName === 'Simple tooth extraction' && self::hasOtherToothProcedureOnDate($memberId, $availmentDate, $toothId, 'Simple tooth extraction')) {
                    return 'Simple tooth extraction cannot be done on a tooth that already has other procedures on the same date.';
                }
                if ($serviceName !== 'Simple tooth extraction' && self::hasToothProcedure($memberId, null, $toothId, ['Simple tooth extraction'])) {
                    return 'Cannot perform other services on a tooth that has been extracted.';
                }
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Create Procedure
    // -------------------------------------------------------------------------

    public static function create(Member $member, array $data, int $clinicId, bool $isCSR = false): string
    {
        $account      = $member->account;
        $appliedFee   = $data['applied_fee'] ?? self::getAppliedFee($clinicId, $data['service_id'], $data['availment_date'] ?? now()->toDateString()) ?? 0;
        $approvalCode = strtoupper(Str::random(8));
        $status       = $isCSR ? Procedure::STATUS_SIGN : Procedure::STATUS_PENDING;

        $hasUnits = false;
        foreach (self::UNIT_INPUTS as $input) {
            if (!empty($data[$input])) { $hasUnits = true; break; }
        }

        if ($hasUnits) {
            foreach (self::UNIT_INPUTS as $input) {
                if (empty($data[$input])) continue;
                foreach ($data[$input] as $value) {
                    $procedure = Procedure::create([
                        'clinic_id'      => $clinicId,
                        'member_id'      => $member->id,
                        'service_id'     => $data['service_id'],
                        'availment_date' => $data['availment_date'] ?? null,
                        'status'         => $status,
                        'quantity'       => $data['quantity'] ?? 1,
                        'approval_code'  => $approvalCode,
                        'applied_fee'    => $appliedFee,
                    ]);

                    ProcedureUnit::create([
                        'procedure_id'   => $procedure->id,
                        'unit_id'        => $input === 'surface' ? $data['tooth_surface'] : ($input === 'canal' ? $data['tooth_canal'] : $value),
                        'quantity'       => 1,
                        'input_quantity' => $data['quantity'] ?? 1,
                        'surface_id'     => ($input === 'surface' || $input === 'canal') ? $value : null,
                    ]);
                }
            }
        } else {
            Procedure::create([
                'clinic_id'      => $clinicId,
                'member_id'      => $member->id,
                'service_id'     => $data['service_id'],
                'availment_date' => $data['availment_date'] ?? null,
                'status'         => $status,
                'quantity'       => 1,
                'approval_code'  => $approvalCode,
                'applied_fee'    => $appliedFee,
            ]);
        }

        // Deduct balance
        $unitCount = 0;
        foreach (self::UNIT_INPUTS as $input) {
            if (!empty($data[$input])) $unitCount += count((array) $data[$input]);
        }
        $unitCount = max($unitCount, 1);

        if ($account->mbl_type === 'Fixed') {
            self::deductMbl($member, $appliedFee * $unitCount);
        }
        ServiceQuantityService::deduct($member, $data['service_id'], $unitCount);

        return $approvalCode;
    }

    // -------------------------------------------------------------------------
    // Cancel Procedure
    // -------------------------------------------------------------------------

    public static function cancel(Procedure $procedure, ?string $reason, bool $isCSR = false): bool
    {
        $allowedStatuses = $isCSR
            ? [Procedure::STATUS_PENDING, Procedure::STATUS_SIGN]
            : [Procedure::STATUS_PENDING];

        if (!in_array($procedure->status, $allowedStatuses)) {
            return false;
        }

        $procedure->update([
            'status'  => Procedure::STATUS_CANCELLED,
            'remarks' => $reason,
        ]);

        $member = $procedure->member;
        if ($member && $member->account) {
            $unitCount = max($procedure->units()->count(), 1);
            ServiceQuantityService::returnQuantity($member, $procedure->service_id, $unitCount);

            if ($member->account->mbl_type === 'Fixed') {
                self::returnMbl($member, $procedure->applied_fee * $unitCount);
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // MBL Balance Check
    // -------------------------------------------------------------------------

    public static function getMblError(Member $member, array $data, float $appliedFee): ?string
    {
        if ($member->account->mbl_type !== 'Fixed') return null;

        $totalUnits = 0;
        foreach (self::UNIT_INPUTS as $input) {
            if (!empty($data[$input])) $totalUnits += count((array) $data[$input]);
        }
        $totalFee = $appliedFee * max($totalUnits, 1);

        // For SHARED plans, check the principal member's balance (shared across family)
        $balance = self::getFamilyMblBalance($member);

        if ($balance < $totalFee) {
            return "Total fee (₱" . number_format($totalFee, 2) . ") exceeds MBL balance (₱" . number_format($balance, 2) . ").";
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Private query helpers
    // -------------------------------------------------------------------------

    private static function hasOtherProcedures(int $memberId, int $clinicId, string $date): bool
    {
        return Procedure::forMember($memberId)->forClinic($clinicId)
            ->where('availment_date', $date)
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private static function hasOtherProceduresOnUnit(int $memberId, int $clinicId, string $date, int $unitId): bool
    {
        return Procedure::forMember($memberId)->forClinic($clinicId)
            ->where('availment_date', $date)
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
            ->exists();
    }

    private static function hasProcedureByName(int $memberId, int $clinicId, string $date, string $serviceName): bool
    {
        return Procedure::forMember($memberId)->forClinic($clinicId)
            ->where('availment_date', $date)
            ->whereHas('service', fn($q) => $q->where('name', $serviceName))
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private static function hasProcedureOnUnit(int $memberId, int $clinicId, string $date, string $serviceName, int $unitId): bool
    {
        return Procedure::forMember($memberId)->forClinic($clinicId)
            ->where('availment_date', $date)
            ->whereHas('service', fn($q) => $q->where('name', $serviceName))
            ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private static function hasToothProcedure(int $memberId, ?string $date, int $toothId, array $serviceNames): bool
    {
        $query = Procedure::forMember($memberId)
            ->whereHas('service', fn($q) => $q->whereIn('name', $serviceNames))
            ->whereHas('units', fn($q) => $q->where('unit_id', $toothId))
            ->whereNotIn('status', [Procedure::STATUS_CANCELLED, Procedure::STATUS_REJECT]);

        if ($date) {
            $query->where('availment_date', $date)
                ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED]);
        }

        return $query->exists();
    }

    private static function hasOtherToothProcedureOnDate(int $memberId, string $date, int $toothId, string $excludeService): bool
    {
        return Procedure::forMember($memberId)
            ->where('availment_date', $date)
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->whereHas('units', fn($q) => $q->where('unit_id', $toothId))
            ->whereHas('service', fn($q) => $q->where('name', '!=', $excludeService))
            ->exists();
    }

    // -------------------------------------------------------------------------
    // MBL Helpers (SHARED-aware)
    // -------------------------------------------------------------------------

    private static function getFamilyMblBalance(Member $member): float
    {
        $account = $member->account;
        if (!$account) return 0;

        // SHARED plan — balance is shared across all members with same card_number
        if (strtoupper($account->plan_type) === 'SHARED' && $member->card_number) {
            return (float) Member::where('card_number', $member->card_number)
                ->where('account_id', $account->id)
                ->min('mbl_balance') ?? 0;
        }

        return (float) $member->mbl_balance;
    }

    private static function deductMbl(Member $member, float $amount): void
    {
        $account = $member->account;
        if (!$account) return;

        if (strtoupper($account->plan_type) === 'SHARED' && $member->card_number) {
            // Deduct from ALL members with same card_number
            Member::where('card_number', $member->card_number)
                ->where('account_id', $account->id)
                ->each(function (Member $m) use ($amount) {
                    $m->update(['mbl_balance' => max(0, $m->mbl_balance - $amount)]);
                });
        } else {
            $member->update(['mbl_balance' => max(0, $member->mbl_balance - $amount)]);
        }
    }

    public static function returnMbl(Member $member, float $amount): void
    {
        $account = $member->account;
        if (!$account) return;

        if (strtoupper($account->plan_type) === 'SHARED' && $member->card_number) {
            // Return to ALL members with same card_number
            Member::where('card_number', $member->card_number)
                ->where('account_id', $account->id)
                ->each(function (Member $m) use ($amount) {
                    $m->update(['mbl_balance' => $m->mbl_balance + $amount]);
                });
        } else {
            $member->update(['mbl_balance' => $member->mbl_balance + $amount]);
        }
    }
}
