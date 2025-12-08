<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dental Billing Summary - Northern Dental Specialists</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            margin: 10px;
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

    </style>
</head>
<body>

    <!-- Header Information -->
    <table class="header-table">
        <tr>
            <td><strong>Clinic:</strong></td>
            <td colspan="3">{{ $clinicDetails->clinic_name }}</td>
        </tr>
        <tr>
            <td><strong>Dentist:</strong></td>
            <td colspan="3">{{ $clinicDetails->dentists->first()->name }}</td>
        </tr>
        <tr>
            <td><strong>BIR Registered Name:</strong></td>
            <td colspan="3">{{ $clinicDetails->registered_name }}</td>
        </tr>
        <tr>
            <td><strong>TIN:</strong></td>
            <td>{{ $clinicDetails->tax_identification_no }}</td>
        </tr>
        <tr>
            <td><strong>Branch Code:</strong></td>
            <td>000</td>
        </tr>
        <tr>
            <td><strong>Address:</strong></td>
            <td colspan="3">{{ $clinicDetails->complete_address }}</td>
        </tr>
        <tr>
            <td><strong>Tax Type:</strong></td>
            <td>{{ $clinicDetails->vat_type }}</td>
        </tr>
        <tr>
            <td><strong>EWT:</strong></td>
            <td>{{ $clinicDetails->withholding_tax }}</td>
        </tr>
    </table>

    <!-- Claims Table -->
    <table>
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>Name of Patient</th>
                <th>Company</th>
                <th>Main Company</th>
                <th>Card No.</th>
                <th>Service Name</th>
                <th>Tooth No.</th>
                <th>Rate</th>
                <th>EWT</th>
                <th>NET</th>
            </tr>
        </thead>
        <tbody>
            <!-- Example Row -->
            @foreach ($claims as $claim)

            <tr>
                <td>{{ \Carbon\Carbon::parse($claim->availment_date)->format('F d, Y') }}</td>
                <td>{{ $claim->member->first_name }} {{ $claim->member->last_name }}</td>
                <td>{{ $claim->member->account->company_name }}</td>
                <td>{{ $claim->member->account->company_name }}</td>
                <td>{{ $claim->member->card_number }}</td>
                <td>{{ $claim->service->name }}</td>
                <td></td>
                <td>
                    {{ $claim->clinic_service_fee }}
                </td>
                <td>
                    {{ $claim->ewt }}
                </td>
                <td>
                    {{ $claim->net }}
                </td>
            </tr>
            @endforeach
            <!-- Add more rows dynamically as needed -->
            <tr class="totals">
                <td colspan="7" class="text-right">Totals:</td>
                <td>42,000.00</td>
                <td>2,100.00</td>
                <td>39,900.00</td>
            </tr>
        </tbody>
    </table>

    <!-- Main Company Totals -->
    <table>
        <thead>
            <tr>
                <th>Main Company</th>
                <th>Rate</th>
                <th>EWT</th>
                <th>NET</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>*Apex EB Consulting, Inc.</td>
                <td>2,500.00</td>
                <td>125.00</td>
                <td>2,375.00</td>
            </tr>
            <tr>
                <td>*COCOLIFE Healthcare</td>
                <td>7,000.00</td>
                <td>350.00</td>
                <td>6,650.00</td>
            </tr>
            <tr class="totals">
                <td><strong>Grand Total</strong></td>
                <td><strong>42,000.00</strong></td>
                <td><strong>2,100.00</strong></td>
                <td><strong>39,900.00</strong></td>
            </tr>
        </tbody>
    </table>



    <!-- Total Forms & Checked By -->
    <table class="float-right">
        <tr>
            <th colspan="3">OPERATIONS</th>
        </tr>
        <tr>
            <td><strong>Total No. of Forms:</strong> 66</td>
            <td><strong>Checked By:</strong> _________________________</td>
            <td>4/8/2025</td>
        </tr>
    </table>
    <div class="clear"></div>

    <!-- Finance Table -->
    <table class="float-right">
        <thead>
            <tr>
                <th colspan="2">FINANCE</th>
            </tr>
            <tr>
                <th>RECEIVED</th>
                <th>PAID</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>BY: _________________________</td>
                <td>CHECK # ________________</td>
            </tr>
            <tr>
                <td>DATE: ______________________</td>
                <td>DATE: __________________</td>
            </tr>
        </tbody>
    </table>
    <div class="clear"></div>

    <!-- Account / Finance -->
    <table class="float-right">
        <tr>
            <td><strong>Account Name:</strong></td>
            <td>NORTHERN DENTAL SPECIALIST</td>
        </tr>
        <tr>
            <td><strong>Bank / Branch:</strong></td>
            <td>Bangko Nuestra Senora Del Pilar, Inc. (A Rural Bank)</td>
        </tr>
        <tr>
            <td><strong>Account No.:</strong></td>
            <td>57-00016-6</td>
        </tr>
        <tr>
            <td><strong>Remarks:</strong></td>
            <td>Priority / Consider Late Billing</td>
        </tr>
    </table>

    <div class="clear"></div>

</body>
</html>
