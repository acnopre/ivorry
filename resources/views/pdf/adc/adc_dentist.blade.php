<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approved Dental Claims</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            margin: 10px;
            padding-top: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .header-table td {
            border: none;
            padding: 2px 0;
        }

        .totals {
            font-weight: bold;
            background-color: #ddd;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .float-right {
            float: right;
            width: 40%;
            margin-top: 10px;
        }

        .clear {
            clear: both;
        }

        .signature {
            height: 60px;
            vertical-align: bottom;
        }

        /* Prevent Dompdf page break inside tables */
        tr,
        td,
        th {
            page-break-inside: avoid;
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.1);
            /* light gray, transparent */
            z-index: -1000;
            pointer-events: none;
        }

        .sequence-number {
            position: fixed;
            top: 10px;
            right: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            font-size: 10px;
            text-align: right;
            color: #333;
        }

        .no-outer-border {
            border: none !important;
        }

        .no-outer-border>tr>th,
        .no-outer-border>tr>td {
            border: 1px solid black;
            /* keep inner borders */
        }

        .page-break {
            page-break-before: always;
            /* forces new page */
        }

    </style>
</head>
<div class="sequence-number">
    DENTIST COPY | {{ $sequenceNumber }}
</div>
<br>
<body>
    <!-- Watermark for Copy Type -->
    @if(!empty($copyLabel))
    <div class="watermark">
        {{ strtoupper($copyLabel) }}
    </div>
    @endif
    <!-- Header Information -->
    @include('pdf.adc.header_information')

    <!-- Claims Table -->
    @include('pdf.adc.claims_table')

    <!-- Footer on second page as well -->
    <div class="footer">
        {{ strtoupper($copyLabel ?? 'ORIGINAL') }} - Printed on {{ now()->format('Y-m-d H:i') }}
    </div>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 10;
            $x = 20; // padding from left
            $y = $pdf->get_height() - 50; // 15 points from bottom
            $pdf->page_text($x, $y, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, $size);
        }
    </script>

</body>
</html>
