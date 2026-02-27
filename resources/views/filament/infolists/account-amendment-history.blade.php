@php
$amendments = \App\Models\AccountAmendment::where('account_id', $record->id)
    ->where('endorsement_status', 'APPROVED')
    ->with(['services.service', 'requestedBy', 'approvedBy'])
    ->orderByDesc('created_at')
    ->get();
@endphp

<div class="space-y-4">
    @forelse($amendments as $amendment)
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button 
                type="button"
                onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180');"
                class="w-full bg-gradient-to-r from-info-50 to-gray-50 px-6 py-4 text-left dark:from-info-950/30 dark:to-gray-900/50 hover:from-info-100 hover:to-gray-100 dark:hover:from-info-950/50 dark:hover:to-gray-900 transition-all"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-info-100 dark:bg-info-900/50">
                            <svg class="h-5 w-5 text-info-600 dark:text-info-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Amendment #{{ $loop->iteration }}
                            </h3>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $amendment->created_at->format('M d, Y') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $amendment->created_at->format('h:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-100 px-3 py-1 text-xs font-semibold text-success-700 dark:bg-success-900/50 dark:text-success-300">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Approved
                        </span>
                        <svg class="chevron h-5 w-5 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </button>

            <div class="hidden border-t border-gray-100 dark:border-gray-700">
                <div class="bg-gray-50 px-6 py-4 dark:bg-gray-900/30">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Account Information</h4>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Company Name</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->company_name }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Policy Code</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->policy_code }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">HIP</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->hip ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Card Used</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->card_used ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Effective Date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->effective_date ? \Carbon\Carbon::parse($amendment->effective_date)->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Expiration Date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->expiration_date ? \Carbon\Carbon::parse($amendment->expiration_date)->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($amendment->mbl_type)
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">MBL Type</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->mbl_type }}</p>
                        </div>
                        @endif
                        @if($amendment->mbl_amount)
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">MBL Amount</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">₱{{ number_format($amendment->mbl_amount, 2) }}</p>
                        </div>
                        @endif
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Requested By</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->requestedBy?->name ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Approved By</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $amendment->approvedBy?->name ?? 'N/A' }}</p>
                        </div>
                        @if($amendment->remarks)
                        <div class="col-span-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Remarks</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $amendment->remarks }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Services</h4>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Service</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach($amendment->services as $service)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $service->service->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        @if($service->is_unlimited)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-info-100 px-2.5 py-1 text-xs font-semibold text-info-700 dark:bg-info-900/50 dark:text-info-300">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                                </svg>
                                                Unlimited
                                            </span>
                                        @else
                                            <span class="font-semibold">{{ $service->quantity ?? 0 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $service->remarks ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900/30">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-800">
                <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No amendment history</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This account has no approved amendments yet.</p>
        </div>
    @endforelse
</div>
