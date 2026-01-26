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
                    <th colspan="4" class="text-center">OPERATIONS</th>
                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Total No. of Forms:</strong></td>
                    <td colspan="3"></td>

                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Checked By:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>
                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Date Processed:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

                </tr>

                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Due Date:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

                </tr>
            </table>
        </td>


        <td style="width: 33%; vertical-align: top; border: none; padding: 1;">
            <table class="report-table no-outer-border" style="width: 100%;">
                <tr>
                    <th colspan="4" class="text-center">FINANCE</th>
                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Paid:</strong></td>
                    <td colspan="3"></td>

                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Bank:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>
                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Check #:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

                </tr>

                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Date:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

                </tr>

                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Received by:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

                </tr>
                <tr>
                    <td style="width: 25%; white-space: normal; word-wrap: break-word;"><strong>Received Date:</strong></td>
                    <td colspan="3">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</td>

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
