<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;

class ReportsExport implements FromQuery, WithHeadings, WithMapping, WithEvents, WithCustomStartCell
{
    public function __construct(
        protected Builder $query,
        protected string $type,
        protected array $filters = [] // account_name, from_date, to_date, member_status
    ) {}

    public function query()
    {
        return $this->query;
    }

    // Column headings start at row 9 (below filter box)
    public function startCell(): string
    {
        return 'A9';
    }

    public function headings(): array
    {
        return match ($this->type) {
            'members' => ['Account Name', 'Name',  'Member Type', 'Card Type', 'Gender', 'Email', 'Status', 'Inactive Date', 'Date Added'],
            'dentists' => ['Clinic Name', 'Dentist Name', 'Specialization', 'Status', 'Date Added'],
            'clinics' => ['Clinic Name', 'Registered Name', 'Branch', 'Business Type', 'Vat Type', 'Witholding Tax', 'Accreditation Status', 'Date Added'],
            'procedures' => ['Member Name', 'Clinic Name', 'Procedure Name', 'Units', 'Applied Fee', 'Availment Date', 'Approval Code', 'Status', 'Date Added'],
            'accounts' => ['Account Name', 'Policy Code', 'HIP', 'Effective Date', 'Expiration Date', 'Plan Type', 'Coverage Period Type', 'Account Status', 'Date Created'],
            default => [],
        };
    }

    public function map($row): array
    {
        return match ($this->type) {
            'members' => [
                optional($row->account)->company_name,
                $row->full_name,
                $row->member_type,
                $row->card_number,
                $row->gender,
                $row->email,
                $row->status,
                optional($row->inactive_date)?->format('Y-m-d'),
                optional($row->created_at)?->format('Y-m-d'),
            ],

            'dentists' => [
                $row->clinic?->clinic_name,
                trim($row->first_name . ' ' . $row->last_name),
                $row->specializations?->pluck('name')->implode(', '),
                $row->status,
                optional($row->created_at)->format('Y-m-d'),
            ],

            'clinics' => [
                $row->clinic_name,
                $row->registered_name,
                $row->is_branch ? 'Yes' : 'No',
                $row->business_type,
                $row->vat_type,
                $row->witholding_tax,
                $row->accreditation_status,
                optional($row->created_at)->format('Y-m-d'),
            ],

            'procedures' => [
                trim($row->member?->first_name . ' ' . $row->member?->last_name),
                $row->clinic?->clinic_name,
                $row->service?->name,
                $row->units,
                $row->applied_fee,
                optional($row->availment_date)->format('Y-m-d'),
                $row->approval_code,
                $row->status,
                optional($row->created_at)->format('Y-m-d'),
            ],

            'accounts' => [
                $row->company_name,
                $row->policy_code,
                $row->hip,
                optional($row->effective_date)->format('Y-m-d'),
                optional($row->expiration_date)->format('Y-m-d'),
                $row->plan_type,
                $row->coverage_period_type,
                $row->account_status,
                optional($row->created_at)->format('Y-m-d'),
            ],

            default => [],
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // === MEMBER FILTER BOX ===
                if ($this->type === 'members') {
                    $filters = $this->filters;

                    // Row 1: Filters title
                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'center'],
                    ]);

                    // Row 2: Account Name
                    $sheet->setCellValue('B2', 'Account Name');
                    $sheet->setCellValue('C2', $filters['account_name'] ?? 'All');

                    // Row 4: Coverage Period
                    $sheet->setCellValue('B4', 'Coverage Period');
                    $sheet->setCellValue('B5', 'From Date');
                    $sheet->setCellValue('C5', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('B6', 'To Date');
                    $sheet->setCellValue('C6', $filters['to_date'] ?? '-');

                    // Row 7: Member Status
                    $sheet->setCellValue('B7', 'Member Status');
                    $sheet->setCellValue('C7', $filters['member_status'] ?? 'All');

                    // Style text & values
                    $sheet->getStyle('B2:C7')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('B2:C7')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                }
            },
        ];
    }
}
