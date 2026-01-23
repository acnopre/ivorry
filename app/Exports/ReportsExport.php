<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected Builder $query,
        protected string $type
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return match ($this->type) {
            'members' => ['Account Name', 'Name',  'Member Type', 'Card Type', 'Gender', 'Email', 'Status', 'Inactive Date', 'Created At'],
            'dentists' => ['Name', 'Specialization', 'Status'],
            'clinics' => ['Clinic Name', 'Status'],
            'claims' => ['Approval Code', 'Member', 'Status', 'Availment Date'],
            default => [],
        };
    }

    public function map($row): array
    {
        return match ($this->type) {
            'members' => [
                optional($row->account)->company_name, // <-- relationship
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
                $row->name,
                $row->specialization,
                $row->status,
            ],

            'clinics' => [
                $row->clinic_name,
                $row->status,
            ],

            'claims' => [
                $row->approval_code,
                optional($row->member)->full_name,
                $row->status,
                optional($row->availment_date)->format('Y-m-d'),
            ],

            default => [],
        };
    }
}
