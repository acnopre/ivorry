<?php

namespace App\Exports;

use App\Models\ImportLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ImportLogDetailsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $importLogId;

    public function __construct($importLogId)
    {
        $this->importLogId = $importLogId;
    }

    public function collection()
    {
        return ImportLog::find($this->importLogId)
            ->items()
            ->orderBy('row_number')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Row Number',
            'Status',
            'Error Message',
            'Raw Data',
        ];
    }

    public function map($item): array
    {
        return [
            $item->row_number,
            ucfirst($item->status),
            $item->message ?? '—',
            $item->raw_data,
        ];
    }
}
