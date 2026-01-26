<table>
    <thead>
        <tr>
            <th>HIP</th>
            <th>Rate</th>
            <th>VAT</th>
            <th>EWT</th>
            <th>NET</th>
        </tr>
    </thead>
    <tbody>
        @foreach($accounts as $data)
        <tr>
            <td>{{ $data['hip'] }}</td>
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
