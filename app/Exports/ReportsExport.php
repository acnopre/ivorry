<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Clinics;
use App\Models\Claim;
use App\Models\Procedure;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportsExport implements FromCollection, WithHeadings
{
    public function __construct(
        public string $reportType,
        public ?string $status = null,
        public ?string $fromDate = null,
        public ?string $toDate = null
    ) {}

    public function collection()
    {
        $query = match ($this->reportType) {
            'members' => User::whereHas('roles', fn($q) => $q->where('name', 'Member'))
                ->select('name', 'email', 'status', 'created_at'),
            'dentists' => User::whereHas('roles', fn($q) => $q->where('name', 'Dentist'))
                ->select('name', 'specialization', 'status', 'created_at'),
            'clinics' => Clinics::select('clinic_name', 'registered_name', 'accreditation_status', 'created_at'),
            'claims' => Claim::select('claim_number', 'status', 'amount', 'created_at'),
            'soa' => DB::table('statements')->select('statement_number', 'total_amount', 'status', 'created_at'),
            'csr' => Procedure::select('procedure_name', 'status', 'created_at'),
            default => null,
        };

        if ($this->status && $query) {
            $query->where('status', $this->status)
                ->orWhere('accreditation_status', $this->status)
                ->orWhere('approval_status', $this->status);
        }

        if ($this->fromDate && $this->toDate && $query) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ]);
        }

        return $query?->get() ?? collect();
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'members' => ['Name', 'Email', 'Status', 'Created At'],
            'dentists' => ['Name', 'Specialization', 'Status', 'Created At'],
            'clinics' => ['Clinic Name', 'Registered Name', 'Accreditation', 'Created At'],
            'claims' => ['Claim Number', 'Status', 'Amount', 'Created At'],
            'soa' => ['Statement #', 'Total Amount', 'Status', 'Created At'],
            'csr' => ['Procedure Name', 'Status', 'Created At'],
            default => [],
        };
    }
}
