<div>
    @php
    $usageHistory = \App\Models\Procedure::with(['service', 'member'])
        ->whereHas('member', fn($q) => $q->where('account_id', $record->id))
        ->where('status', 'signed')
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
                    <th class="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300">Fee</th>
                    <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Qty</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Approval Code</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                @foreach($usageHistory as $usage)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $usage->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $usage->member->first_name }} {{ $usage->member->last_name }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $usage->service->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-mono">₱{{ number_format($usage->applied_fee, 2) }}</td>
                    <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100 font-mono">{{ $usage->quantity }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $usage->approval_code }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2 text-gray-400" />
        <p class="text-sm">No usage history found for this account.</p>
    </div>
    @endif
</div>
