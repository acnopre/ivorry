<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approved Generated Claims</title>
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
    {{ $sequenceNumber }}
</div>
<br>
<body>
    <div class="watermark">
        ORIGINAL
    </div>
    <!-- Header Information -->
    @include('pdf.adc.header_information')

    <!-- Claims Table -->
    @include('pdf.adc.claims_table')
    <!-- Main Company Totals -->
    @include('pdf.adc.main_company')

    <!--Footer -->
    @include('pdf.adc.footer')

    <div class="page-break"></div>

    <!-- Header Information -->
    @include('pdf.adc.header_information')

    <!-- Claims Table -->
    @include('pdf.adc.claims_table')

</body>
</html>
