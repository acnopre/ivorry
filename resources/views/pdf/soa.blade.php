<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement of Account</title>
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
            <td colspan="3">{{ $dentist['first_name'] }} {{ $dentist['last_name'] }}</td>
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
                <th>Units</th>
                <th>Rate</th>
                <th>VAT</th>
                <th>EWT</th>
                <th>NET</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($claims as $claim)
            <tr>
                <td>{{ \Carbon\Carbon::parse($claim->availment_date)->format('F d, Y') }}</td>
                <td>{{ $claim->member->first_name }} {{ $claim->member->last_name }}</td>
                <td>{{ $claim->member->account->company_name }}</td>
                <td>{{ $claim->member->account->company_name }}</td>
                <td>{{ $claim->member->card_number }}</td>
                <td>{{ $claim->service->name }}</td>
                <td>
                    @forelse ($claim->units as $unit)
                    {{ $unit->unitType?->name ?? '—' }} : {{ $unit->name ?? '—' }}
                    @if (! $loop->last), @endif
                    @empty
                    —
                    @endforelse
                </td>

                <td>₱{{ number_format($claim->clinic_service_fee, 2) }}</td>
                <td>₱{{ number_format($claim->vat_amount, 2) }}</td>
                <td>₱{{ number_format($claim->ewt_amount, 2) }}</td>
                <td>₱{{ number_format($claim->net, 2) }}</td>
            </tr>
            @endforeach

            {{-- TOTALS --}}
            <tr class="totals">
                <td colspan="7" class="text-right"><strong>Totals:</strong></td>
                <td><strong>₱{{ number_format($totalClinicFee, 2) }}</strong></td>
                <td><strong>₱{{ number_format($totalVat, 2) }}</strong></td>
                <td><strong>₱{{ number_format($totalEwt, 2) }}</strong></td>
                <td><strong>₱{{ number_format($totalNet, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>


    <!-- Main Company Totals -->
    <table>
        <thead>
            <tr>
                <th>Main Company</th>
                <th>Rate</th>
                <th>VAT</th>
                <th>EWT</th>
                <th>NET</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $data)
            <tr>
                <td>{{ $data['account_name'] }}</td>
                <td>₱{{ number_format($data['total_rate'], 2) }}</td>
                <td>₱{{ number_format($data['total_vat'], 2) }}</td>
                <td>₱{{ number_format($data['total_ewt'], 2) }}</td>
                <td>₱{{ number_format($data['total_net'], 2) }}</td>
            </tr>
            @endforeach

            {{-- GRAND TOTAL --}}
            <tr class="totals">
                <td><strong>Grand Total</strong></td>
                <td><strong>₱{{ number_format($grandTotalRate, 2) }}</strong></td>
                <td><strong>₱{{ number_format($grandTotalVat, 2) }}</strong></td>
                <td><strong>₱{{ number_format($grandTotalEwt, 2) }}</strong></td>
                <td><strong>₱{{ number_format($grandTotalNet, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>



    <!-- Total Forms & Checked By -->
    <table class="float-right">
        <tr>
            <th colspan="3">OPERATIONS</th>
        </tr>
        <tr>
            <td><strong>Total No. of Forms:</strong> {{ $claims->count() }}</td>
            <td><strong>Checked By:</strong> _________________________</td>
            <td><strong>Date:</strong> ___________</td>
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
            <td>{{ $clinicDetails->bank_account_name }}</td>
        </tr>
        <tr>
            <td><strong>Bank / Branch:</strong></td>
            <td>{{ $clinicDetails->bank_branch }}</td>
        </tr>
        <tr>
            <td><strong>Account No.:</strong></td>
            <td>{{ $clinicDetails->bank_account_number }}</td>
        </tr>
        <tr>
            <td><strong>Remarks:</strong></td>
            <td>{{ $clinicDetails->remarks }}</td>
        </tr>
    </table>

    <div class="clear"></div>

</body>
</html>
