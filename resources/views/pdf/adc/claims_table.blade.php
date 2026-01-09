<table>
    <thead>
        <tr>
            <th>Transaction Date</th>
            <th>Name of Patient</th>
            <th>Company</th>
            {{-- <th>Main Company</th> --}}
            {{-- <th>Card No.</th> --}}
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
            {{-- <td>{{ $claim->member->account->company_name }}</td> --}}
            {{-- <td>{{ $claim->member->card_number }}</td> --}}
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
            <td colspan="5" class="text-right"><strong>Totals:</strong></td>
            <td><strong>₱{{ number_format($totalClinicFee, 2) }}</strong></td>
            <td><strong>₱{{ number_format($totalVat, 2) }}</strong></td>
            <td><strong>₱{{ number_format($totalEwt, 2) }}</strong></td>
            <td><strong>₱{{ number_format($totalNet, 2) }}</strong></td>
        </tr>
    </tbody>
</table>
