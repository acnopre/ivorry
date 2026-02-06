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
        return match ($this->type) {
            'members' => 'A9',
            'dentists' => 'A10',
            'clinics' => 'A12',
            'procedures' => 'A9',
            'accounts' => 'A9'
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'members' => ['Account Name', 'HIP', 'Name',  'Member Type', 'Card Type', 'Gender', 'Status', 'Account Effective Date', 'Account Expiration Date', 'Inactive Date', 'Date Added'],
            'dentists' => ['Clinic Name', 'Dentist Name', 'Specialization', 'Status', 'Date Added'],
            'clinics' => ['Clinic Name', 'Registered Name', 'Address', 'Branch', 'Business Type', 'Vat Type', 'Witholding Tax', 'Accreditation Status', 'Date Added'],
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
                optional($row->account)->hip,
                $row->full_name,
                $row->member_type,
                $row->card_number,
                $row->gender,
                $row->status,
                $row->account->effective_date->format('Y-m-d'),
                $row->account->expiration_date->format('Y-m-d'),
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
                $row->complete_address,
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
                // Parse units from pivot
                $row->units
                    ->map(function ($unit) {
                        $unitTypeName = $unit->unitType?->name ?? 'N/A';
                        $unitName = $unit->name ?? 'N/A';
                        $surface = $unit->pivot->surface?->name ? ' — Surface: ' . $unit->pivot->surface->name : '';

                        return $unitTypeName . ': ' . $unitName . $surface;
                    })
                    ->join(', '),
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

                if ($this->type === 'members') {
                    $filters = $this->filters;

                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'left'],
                    ]);

                    $sheet->setCellValue('A2', 'Account Name');
                    $sheet->setCellValue('B2', $filters['account_name'] ?? 'All');

                    $sheet->setCellValue('A3', 'From Date');
                    $sheet->setCellValue('B3', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('A4', 'To Date');
                    $sheet->setCellValue('B4', $filters['to_date'] ?? '-');

                    $sheet->setCellValue('A5', 'Member Status');
                    $sheet->setCellValue('B5', $filters['member_status'] ?? 'All');

                    $sheet->setCellValue('A6', 'Member Type');
                    $sheet->setCellValue('B6', $filters['member_type'] ?? 'All');

                    // Style text & values
                    $sheet->getStyle('A2:B6')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('B2:C6')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $sheet->getStyle('A1:C6')->getAlignment()->setWrapText(true);

                    foreach (range('A', 'Z') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                } else if ($this->type === 'dentists') {
                    $filters = $this->filters;

                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'left'],
                    ]);

                    $sheet->setCellValue('A2', 'Clinic Name');
                    $sheet->setCellValue('B2', $filters['clinic_name'] ?? 'All');

                    $sheet->setCellValue('A3', 'From Date');
                    $sheet->setCellValue('B3', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('A4', 'To Date');
                    $sheet->setCellValue('B4', $filters['to_date'] ?? '-');

                    $sheet->setCellValue('A5', 'Specialization');
                    $sheet->setCellValue('B5', $filters['specializations'] ?? 'All');

                    $sheet->setCellValue('A6', 'Region');
                    $sheet->setCellValue('B6', $filters['region'] ?? 'All');

                    $sheet->setCellValue('A7', 'Province');
                    $sheet->setCellValue('B7', $filters['province'] ?? 'All');

                    $sheet->setCellValue('A8', 'Municipality');
                    $sheet->setCellValue('B8', $filters['municipality'] ?? 'All');

                    // Style text & values
                    $sheet->getStyle('A2:B7')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('A2:B8')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $sheet->getStyle('A1:C7')->getAlignment()->setWrapText(true);

                    foreach (range('A', 'Z') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                } else if ($this->type === 'clinics') {
                    $filters = $this->filters;

                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'left'],
                    ]);

                    $sheet->setCellValue('A2', 'Clinic Name');
                    $sheet->setCellValue('B2', $filters['clinic_name'] ?? 'All');

                    $sheet->setCellValue('A3', 'From Date');
                    $sheet->setCellValue('B3', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('A4', 'To Date');
                    $sheet->setCellValue('B4', $filters['to_date'] ?? '-');

                    $sheet->setCellValue('A5', 'Accreditation Status');
                    $sheet->setCellValue('B5', $filters['accreditation_status'] ?? 'All');

                    $sheet->setCellValue('A6', 'Vat Type');
                    $sheet->setCellValue('B6', $filters['vat_type'] ?? 'All');

                    $sheet->setCellValue('A7', 'Business Type');
                    $sheet->setCellValue('B7', $filters['business_type'] ?? 'All');

                    $sheet->setCellValue('A8', 'Region');
                    $sheet->setCellValue('B8', $filters['region'] ?? 'All');

                    $sheet->setCellValue('A9', 'Province');
                    $sheet->setCellValue('B9', $filters['province'] ?? 'All');

                    $sheet->setCellValue('A10', 'Municipality');
                    $sheet->setCellValue('B10', $filters['municipality'] ?? 'All');


                    // Style text & values
                    $sheet->getStyle('A2:B10')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('A2:B10')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $sheet->getStyle('A1:C10')->getAlignment()->setWrapText(true);

                    foreach (range('A', 'Z') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                } else if ($this->type === 'accounts') {
                    $filters = $this->filters;

                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'left'],
                    ]);

                    $sheet->setCellValue('A2', 'HIP');
                    $sheet->setCellValue('B2', $filters['hip'] ?? 'All');

                    $sheet->setCellValue('A3', 'From Date');
                    $sheet->setCellValue('B3', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('A4', 'To Date');
                    $sheet->setCellValue('B4', $filters['to_date'] ?? '-');

                    $sheet->setCellValue('A5', 'Plan Type');
                    $sheet->setCellValue('B5', $filters['plan_type'] ?? 'All');

                    $sheet->setCellValue('A5', 'Coverage Period Type');
                    $sheet->setCellValue('B5', $filters['coverage_period_type'] ?? 'All');

                    $sheet->setCellValue('A6', 'Endorsement Type');
                    $sheet->setCellValue('B6', $filters['endorsement_type'] ?? 'All');


                    // Style text & values
                    $sheet->getStyle('A2:B7')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('A2:B4')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $sheet->getStyle('A1:C4')->getAlignment()->setWrapText(true);

                    foreach (range('A', 'Z') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                } else if ($this->type === 'procedures') {
                    $filters = $this->filters;

                    $sheet->setCellValue('A1', 'Filters');
                    $sheet->mergeCells('A1:C1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => 'left'],
                    ]);

                    $sheet->setCellValue('A2', 'Procedure Status');
                    $sheet->setCellValue('B2', $filters['procedure_status'] ?? 'All');

                    $sheet->setCellValue('A3', 'From Date');
                    $sheet->setCellValue('B3', $filters['from_date'] ?? '-');
                    $sheet->setCellValue('A4', 'To Date');
                    $sheet->setCellValue('B4', $filters['to_date'] ?? '-');


                    // Style text & values
                    $sheet->getStyle('A2:B7')->applyFromArray([
                        'font' => ['bold' => false, 'size' => 11],
                        'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                    ]);

                    // Add a border box around the filter area
                    $sheet->getStyle('A2:B4')->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $sheet->getStyle('A1:C4')->getAlignment()->setWrapText(true);

                    foreach (range('A', 'Z') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                }
            },
        ];
    }
}
