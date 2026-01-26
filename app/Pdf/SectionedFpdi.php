<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi;

class SectionedFpdi extends Fpdi
{
    public $sectionLabel = '';
    public $sectionTotalPages = 0;
    public $sectionStartPage = 1;
    public $copyLabel = 'Original';

    // Override Footer to show section page numbering
    public function Footer()
    {
        if (empty($this->sectionLabel)) {
            return parent::Footer();
        }

        $this->SetY(-15);
        $this->SetFont('Helvetica', '', 10);
        $currentSectionPage = $this->PageNo() - $this->sectionStartPage + 1;
        $footerText = strtoupper($this->copyLabel)
            . " - " . $this->sectionLabel
            . " | Page {$currentSectionPage} of {$this->sectionTotalPages}";

        $this->Cell(0, 10, $footerText, 0, 0, 'C');
    }
}
