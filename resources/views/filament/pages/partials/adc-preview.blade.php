@if($previewData)
<div class="space-y-4 text-sm text-gray-800 dark:text-gray-200 p-1">
    {{-- Clinic Info --}}
    <div class="fi-section rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="fi-section-header px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
            <span class="font-semibold text-gray-700 dark:text-gray-300">Clinic Information</span>
        </div>
        <div class="fi-section-content p-4 grid grid-cols-2 gap-x-8 gap-y-1.5">
            <div><span class="text-gray-500">Clinic:</span> <span class="font-medium">{{ $previewData['clinic_name'] }}</span></div>
            <div><span class="text-gray-500">Dentist:</span> <span class="font-medium">{{ $previewData['dentist_name'] }}</span></div>
            <div><span class="text-gray-500">BIR Registered Name:</span> <span class="font-medium">{{ $previewData['registered_name'] }}</span></div>
            <div><span class="text-gray-500">TIN:</span> <span class="font-medium">{{ $previewData['tin'] }}</span></div>
            <div><span class="text-gray-500">Branch:</span> <span class="font-medium">{{ $previewData['is_branch'] ? 'YES' : 'NO' }}</span></div>
            <div><span class="text-gray-500">Vat Type:</span> <span class="font-medium">{{ $previewData['vat_type'] }}</span></div>
            <div><span class="text-gray-500">EWT:</span> <span class="font-medium">{{ $previewData['ewt'] }}</span></div>
            @if($previewData['from'] && $previewData['to'])
            <div><span class="text-gray-500">Period:</span> <span class="font-medium">{{ $previewData['from'] }} — {{ $previewData['to'] }}</span></div>
            @endif
            <div class="col-span-2"><span class="text-gray-500">Address:</span> <span class="font-medium">{{ $previewData['address'] }}</span></div>
        </div>
    </div>

    {{-- Claims Table --}}
    <div class="fi-section rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="fi-section-header px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
            <span class="font-semibold text-gray-700 dark:text-gray-300">Claims — {{ count($previewData['claims']) }} record(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-white/10">Date</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-white/10">Patient</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-white/10">Company</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-white/10">Service</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200 dark:border-white/10">Units</th>
                        <th class="px-3 py-2 text-right border-b border-gray-200 dark:border-white/10">Rate</th>
                        <th class="px-3 py-2 text-right border-b border-gray-200 dark:border-white/10">VAT</th>
                        <th class="px-3 py-2 text-right border-b border-gray-200 dark:border-white/10">EWT</th>
                        <th class="px-3 py-2 text-right border-b border-gray-200 dark:border-white/10">NET</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($previewData['claims'] as $claim)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($claim['availment_date'])->format('M d, Y') }}</td>
                        <td class="px-3 py-2 font-medium">{{ $claim['member_name'] }}</td>
                        <td class="px-3 py-2">{{ $claim['company_name'] }}</td>
                        <td class="px-3 py-2">{{ $claim['service_name'] }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $claim['units'] ?: '—' }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($claim['clinic_service_fee'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($claim['vat_amount'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($claim['ewt_amount'], 2) }}</td>
                        <td class="px-3 py-2 text-right font-medium">₱{{ number_format($claim['net'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-white/5 font-semibold border-t border-gray-200 dark:border-white/10">
                    <tr>
                        <td colspan="5" class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">Totals:</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($previewData['total_fee'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($previewData['total_vat'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($previewData['total_ewt'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($previewData['total_net'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
