<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicImportTemplateExport
{
    private Spreadsheet $spreadsheet;
    private Worksheet $lookup;

    private const DATA_ROWS = 200;

    public function download(): StreamedResponse
    {
        $this->spreadsheet = new Spreadsheet();
        $this->buildLookupSheet();
        $this->buildTemplateSheet();

        $writer = new Xlsx($this->spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="import-clinic-template.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function buildLookupSheet(): void
    {
        $this->lookup = $this->spreadsheet->createSheet();
        $this->lookup->setTitle('_Lookup');
        $this->lookup->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

        $regions        = DB::table('regions')->orderBy('name')->get();
        $allProvinces   = DB::table('provinces')->orderBy('name')->get();
        $allMunicipalities = DB::table('municipalities')->orderBy('name')->get();

        $provincesByRegion   = $allProvinces->groupBy('region_id');
        $municipalitiesByProvince = $allMunicipalities->groupBy('province_id');
        $provinceMap = $allProvinces->keyBy('id');

        // -------------------------------------------------------
        // SECTION 1: Region list  (col A = name, col B = key)
        // -------------------------------------------------------
        $this->lookup->setCellValue('A1', 'region_name');
        $this->lookup->setCellValue('B1', 'region_key');

        foreach ($regions as $i => $region) {
            $row = $i + 2;
            $key = $this->toKey($region->name);
            $this->lookup->setCellValue('A' . $row, $region->name);
            $this->lookup->setCellValue('B' . $row, $key);
        }

        // Named range for region list (for dropdown)
        $regionCount = $regions->count();
        $this->addNamedRange('_REGIONS', '$A$2:$A$' . ($regionCount + 1));

        // -------------------------------------------------------
        // SECTION 2: Provinces per region
        // Col C onwards: each region gets one column (name only)
        // Named range key = region key
        // -------------------------------------------------------
        $col = 3; // C
        foreach ($regions as $region) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $key = $this->toKey($region->name);
            $this->lookup->setCellValue($colLetter . '1', $key);

            $provinces = $provincesByRegion->get($region->id, collect());
            foreach ($provinces as $i => $province) {
                $this->lookup->setCellValue($colLetter . ($i + 2), $province->name);
            }

            $count = $provinces->count();
            if ($count > 0) {
                $this->addNamedRange($key, '$' . $colLetter . '$2:$' . $colLetter . '$' . ($count + 1));
            }
            $col++;
        }

        // -------------------------------------------------------
        // SECTION 3: Province key lookup (col after regions)
        // Col = province name → key mapping for VLOOKUP
        // -------------------------------------------------------
        $provinceKeyCol = $col;
        $provinceKeyColLetter = Coordinate::stringFromColumnIndex($col);
        $nextCol = $col + 1;
        $provinceKeyColLetter2 = Coordinate::stringFromColumnIndex($nextCol);

        $this->lookup->setCellValue($provinceKeyColLetter . '1', 'province_name');
        $this->lookup->setCellValue($provinceKeyColLetter2 . '1', 'province_key');

        foreach ($allProvinces as $i => $province) {
            $row = $i + 2;
            $key = $this->toKey($province->name);
            $this->lookup->setCellValue($provinceKeyColLetter . $row, $province->name);
            $this->lookup->setCellValue($provinceKeyColLetter2 . $row, $key);
        }

        $provCount = $allProvinces->count();
        $this->addNamedRange('_PROVINCE_KEYS', '$' . $provinceKeyColLetter . '$2:$' . $provinceKeyColLetter2 . '$' . ($provCount + 1));

        // -------------------------------------------------------
        // SECTION 4: Municipalities per province
        // -------------------------------------------------------
        $col = $nextCol + 1;
        foreach ($provinceMap as $province) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $key = $this->toKey($province->name);
            $this->lookup->setCellValue($colLetter . '1', $key);

            $municipalities = $municipalitiesByProvince->get($province->id, collect());
            foreach ($municipalities as $i => $muni) {
                $this->lookup->setCellValue($colLetter . ($i + 2), $muni->name);
            }

            $count = $municipalities->count();
            if ($count > 0) {
                $this->addNamedRange($key, '$' . $colLetter . '$2:$' . $colLetter . '$' . ($count + 1));
            }
            $col++;
        }
    }

    private function buildTemplateSheet(): void
    {
        $sheet = $this->spreadsheet->getSheet(0);
        $sheet->setTitle('Clinic Import');

        $headers = array_keys(\App\Support\ImportTemplates::clinic());
        $sample  = array_values(\App\Support\ImportTemplates::clinic());

        // Headers
        foreach ($headers as $i => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        // Header style
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8B1C52']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row
        foreach ($sample as $i => $value) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . '2', $value);
        }

        // Auto-width & freeze
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        // Find column letters for location fields
        $regionColIdx       = array_search('region_name', $headers) + 1;
        $provinceColIdx     = array_search('province_name', $headers) + 1;
        $municipalityColIdx = array_search('municipality_name', $headers) + 1;

        $regionCol       = Coordinate::stringFromColumnIndex($regionColIdx);
        $provinceCol     = Coordinate::stringFromColumnIndex($provinceColIdx);
        $municipalityCol = Coordinate::stringFromColumnIndex($municipalityColIdx);

        // Highlight location header cells
        foreach ([$regionCol, $provinceCol, $municipalityCol] as $col) {
            $sheet->getStyle($col . '1')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4789A']],
            ]);
        }

        // Add validations for each data row
        for ($row = 2; $row <= self::DATA_ROWS + 1; $row++) {
            // Region — static named range
            $this->addValidation($sheet, $regionCol . $row, '_REGIONS');

            // Province — INDIRECT using VLOOKUP to get the named range key from region value
            $this->addValidation($sheet, $provinceCol . $row,
                'INDIRECT(VLOOKUP(' . $regionCol . $row . ',_Lookup!$A:$B,2,0))'
            );

            // Municipality — INDIRECT using VLOOKUP to get the named range key from province value
            $this->addValidation($sheet, $municipalityCol . $row,
                'INDIRECT(VLOOKUP(' . $provinceCol . $row . ',_PROVINCE_KEYS,2,0))'
            );
        }

        $this->spreadsheet->setActiveSheetIndex(0);
    }

    private function addValidation(Worksheet $sheet, string $cell, string $formula): void
    {
        $v = new DataValidation();
        $v->setType(DataValidation::TYPE_LIST)
          ->setErrorStyle(DataValidation::STYLE_INFORMATION)
          ->setAllowBlank(true)
          ->setShowDropDown(true)
          ->setShowErrorMessage(false)
          ->setFormula1($formula);
        $sheet->getCell($cell)->setDataValidation($v);
    }

    private function addNamedRange(string $name, string $range): void
    {
        $this->spreadsheet->addNamedRange(new NamedRange($name, $this->lookup, $range));
    }

    private function toKey(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '_', $name);
        $clean = preg_replace('/_+/', '_', $clean);
        $clean = trim($clean, '_');
        if (preg_match('/^[0-9]/', $clean)) {
            $clean = '_' . $clean;
        }
        return strtoupper($clean);
    }
}
