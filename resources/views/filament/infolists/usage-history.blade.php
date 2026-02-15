<div>
    @php
    $usageHistory = \App\Models\Procedure::with(['service', 'member'])
        ->whereHas('member', fn($q) => $q->where('account_id', $record->id))
        ->where('status', 'sign')
        ->orderByDesc('created_at')
        ->get();
    @endphp

    @if($usageHistory->isNotEmpty())
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Date</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Member</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Service</th>
                    @if($record->mbl_type === 'Fixed')
                    <th class="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300">Fee Deducted</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300">Balance After</th>
                    @else
                    <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Qty Deducted</th>
                    <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Qty Remaining</th>
                    @endif
                    <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Approval Code</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                @php
                $runningBalance = $record->mbl_balance;
                @endphp
                @foreach($usageHistory as $usage)
                @php
                if($record->mbl_type === 'Fixed') {
                    $balanceAfter = $runningBalance;
                    $runningBalance += $usage->applied_fee;
                } else {
                    $accountService = $record->services()->where('service_id', $usage->service_id)->first();
                    $currentQty = $accountService?->pivot->quantity ?? 0;
                }
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $usage->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $usage->member->first_name }} {{ $usage->member->last_name }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $usage->service->name }}</td>
                    @if($record->mbl_type === 'Fixed')
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-mono">-₱{{ number_format($usage->applied_fee, 2) }}</td>
                    <td class="px-4 py-3 text-right font-mono font-semibold">₱{{ number_format($balanceAfter, 2) }}</td>
                    @else
                    <td class="px-4 py-3 text-center text-red-600 dark:text-red-400 font-mono">-{{ $usage->quantity }}</td>
                    <td class="px-4 py-3 text-center font-mono">{{ $currentQty }}</td>
                    @endif
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $usage->approval_code }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($record->mbl_type === 'Fixed')
    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center gap-2 text-sm">
            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            <span class="text-blue-800 dark:text-blue-200">
                <span class="font-semibold">Current MBL Balance:</span> 
                <span class="font-bold text-lg">₱{{ number_format($record->mbl_balance, 2) }}</span> 
                of ₱{{ number_format($record->mbl_amount, 2) }}
            </span>
        </div>
    </div>
    @endif
    @else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2 text-gray-400" />
        <p class="text-sm">No usage history found for this account.</p>
    </div>
    @endif
</div>
