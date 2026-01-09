<table style="width: 100%; border: none;">
    <tr>
        <td style="width: 33%; vertical-align: top; border: none; padding: 1;">
            <table class="report-table no-outer-border" style="width: 100%;">
                <tr>
                    <th colspan="1" class="text-center">PREPARED BY:</th>
                </tr>
                <tr>
                    <td>{{ $preparedBy }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 33%; vertical-align: top; border: none; padding: 1;">
            <table class="report-table no-outer-border" style="width: 100%;">
                <tr>
                    <th colspan="3" class="text-center">OPERATIONS</th>
                </tr>
                <tr>
                    <td><strong>Total No. of Forms:</strong>___________</td>
                    <td><strong>Checked By:</strong><br>_____________________</td>
                    <td><strong>Date:</strong><br>___________</td>
                </tr>
            </table>
        </td>

        <td style="width: 33%; vertical-align: top; border: none; padding: 1;">
            <table class="report-table no-outer-border" style="width: 100%;">
                <tr>
                    <th colspan="2" class="text-center">FINANCE</th>
                </tr>
                <tr>
                    <th>RECEIVED</th>
                    <th>PAID</th>
                </tr>
                <tr>
                    <td>BY:<br>_____________________</td>
                    <td>CHECK #<br>_______________</td>
                </tr>
                <tr>
                    <td>DATE:<br>________________</td>
                    <td>DATE:<br>________________</td>
                </tr>
            </table>
        </td>

        <td style="width: 33%; vertical-align: top; border: none; padding: 1;">
            <table class="report-table no-outer-border" style="width: 100%;">
                <tr>
                    <th colspan="2" class="text-center">ACCOUNT</th>
                </tr>
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
        </td>
    </tr>
</table>
