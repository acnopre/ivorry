@php
$amendments = \App\Models\AccountAmendment::where('account_id', $record->id)
->where('endorsement_status', 'APPROVED')
->with(['services.service', 'requestedBy', 'approvedBy', 'hip', 'oldHip'])
->orderByDesc('created_at')
->get();
@endphp

<div class="space-y-4 py-2">
    @forelse($amendments as $amendment)
    @php
    $fields = [
    'Company Name' => [$amendment->old_company_name, $amendment->company_name],
    'Policy Code' => [$amendment->old_policy_code, $amendment->policy_code],
    'HIP' => [$amendment->oldHip?->name, $amendment->hip?->name],
    'Card Used' => [$amendment->old_card_used, $amendment->card_used],
    'Plan Type' => [$amendment->old_plan_type, $amendment->plan_type ?? $record->plan_type],
    'Coverage Type' => [$amendment->old_coverage_type, $amendment->coverage_type ?? $record->coverage_type],
    'Coverage Period' => [$amendment->old_coverage_period_type, $amendment->coverage_period_type],
    'MBL Type' => [$amendment->old_mbl_type, $amendment->mbl_type],
    'MBL Amount' => [
    $amendment->old_mbl_amount ? '₱'.number_format($amendment->old_mbl_amount, 2) : null,
    $amendment->mbl_amount ? '₱'.number_format($amendment->mbl_amount, 2) : null,
    ],
    'Effective Date' => [
    $amendment->old_effective_date ? \Carbon\Carbon::parse($amendment->old_effective_date)->format('M d, Y') : null,
    $amendment->effective_date ? \Carbon\Carbon::parse($amendment->effective_date)->format('M d, Y') : null,
    ],
    'Valid Until Date' => [
    $amendment->old_expiration_date ? \Carbon\Carbon::parse($amendment->old_expiration_date)->format('M d, Y') : null,
    $amendment->expiration_date ? \Carbon\Carbon::parse($amendment->expiration_date)->format('M d, Y') : null,
    ],
    ];
    $changedFields = collect($fields)->filter(fn($v) => ($v[0] !== null || $v[1] !== null) && (string)$v[0] !== (string)$v[1]);
    $unchangedFields = collect($fields)->filter(fn($v) => ($v[0] !== null || $v[1] !== null) && (string)$v[0] === (string)$v[1]);
    $changedServices = $amendment->services->filter(fn($s) =>
    (string)$s->old_quantity !== (string)$s->quantity || (bool)$s->old_is_unlimited !== (bool)$s->is_unlimited
    );
    @endphp

    <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm overflow-hidden" x-data="{ open: false }">

        {{-- Header --}}
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                    <x-heroicon-s-pencil-square class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            Amendment #{{ $amendments->count() - $loop->index }}
                        </span>
                        @if($changedFields->count() > 0)
                        <span class="inline-flex items-center rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">
                            {{ $changedFields->count() }} field{{ $changedFields->count() > 1 ? 's' : '' }} changed
                        </span>
                        @endif
                        @if($changedServices->count() > 0)
                        <span class="inline-flex items-center rounded-full bg-info-100 px-2 py-0.5 text-xs font-medium text-info-700 dark:bg-info-900/30 dark:text-info-400">
                            {{ $changedServices->count() }} service{{ $changedServices->count() > 1 ? 's' : '' }} changed
                        </span>
                        @endif
                    </div>
                    <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-calendar-days class="h-3.5 w-3.5" />
                            {{ $amendment->created_at->format('M d, Y · h:i A') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-user class="h-3.5 w-3.5" />
                            Requested by <strong class="ml-1 text-gray-700 dark:text-gray-300">{{ $amendment->requestedBy?->name ?? '—' }}</strong>
                        </span>
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-check-badge class="h-3.5 w-3.5 text-success-500" />
                            Approved by <strong class="ml-1 text-gray-700 dark:text-gray-300">{{ $amendment->approvedBy?->name ?? '—' }}</strong>
                        </span>
                    </div>
                </div>
            </div>
            <x-heroicon-m-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" ::class="{ 'rotate-180': open }" />
        </button>

        {{-- Body --}}
        <div x-show="open" x-collapse>
            <div class="border-t border-gray-100 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">

                {{-- Changed Fields --}}
                @if($changedFields->count() > 0)
                <div class="px-6 py-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400 flex items-center gap-1.5">
                        <x-heroicon-m-arrow-path class="h-3.5 w-3.5" />
                        Changed Fields
                    </p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($changedFields as $label => [$oldVal, $newVal])
                        <div class="rounded-lg bg-warning-50 dark:bg-warning-900/10 ring-1 ring-warning-200 dark:ring-warning-700/30 px-4 py-3">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $label }}</p>
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400">Before</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 line-through">{{ $oldVal ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-warning-200 text-warning-800 dark:bg-warning-700/40 dark:text-warning-300">After</span>
                                    <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">{{ $newVal ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Unchanged Fields --}}
                @if($unchangedFields->count() > 0)
                <div class="px-6 py-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                        <x-heroicon-m-minus class="h-3.5 w-3.5" />
                        Unchanged Fields
                    </p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($unchangedFields as $label => [$oldVal, $newVal])
                        <div class="rounded-lg bg-gray-50 dark:bg-white/5 px-4 py-3">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ $label }}</p>
                            <p class="mt-0.5 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $newVal ?? '—' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Services --}}
                @if($amendment->services->count() > 0)
                <div class="px-6 py-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-heroicon-m-rectangle-stack class="h-3.5 w-3.5" />
                        Services
                    </p>
                    <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Service</th>
                                    <th class="px-4 py-3 text-center font-semibold">Before</th>
                                    <th class="px-4 py-3 text-center font-semibold">After</th>
                                    <th class="px-4 py-3 text-left font-semibold">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-gray-900">
                                @foreach($amendment->services as $svc)
                                @php
                                $svcChanged = (string)$svc->old_quantity !== (string)$svc->quantity
                                || (bool)$svc->old_is_unlimited !== (bool)$svc->is_unlimited;
                                @endphp
                                <tr class="{{ $svcChanged ? 'bg-warning-50 dark:bg-warning-900/10' : '' }}">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $svc->service->name ?? 'N/A' }}
                                        @if($svcChanged)
                                        <span class="ml-1.5 inline-flex items-center rounded-full bg-warning-100 px-1.5 py-0.5 text-xs text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">changed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                        @if($svc->old_is_unlimited)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">Unlimited</span>
                                        @elseif($svc->old_quantity !== null)
                                        {{ $svc->old_quantity }}
                                        @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($svc->is_unlimited)
                                        <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 font-semibold text-success-700 dark:bg-success-900/30 dark:text-success-400">Unlimited</span>
                                        @else
                                        <span class="font-semibold {{ $svcChanged ? 'text-warning-700 dark:text-warning-300' : 'text-gray-900 dark:text-white' }}">
                                            {{ $svc->quantity ?? 0 }}
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $svc->remarks ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Remarks --}}
                @if($amendment->remarks)
                <div class="px-6 py-4 bg-gray-50 dark:bg-white/5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Remarks</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $amendment->remarks }}</p>
                </div>
                @endif

            </div>
        </div>
    </div>

    @empty
    <div class="flex flex-col items-center justify-center rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 py-16 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
            <x-heroicon-o-document-text class="h-7 w-7 text-gray-400 dark:text-gray-500" />
        </div>
        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No amendment history</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This account has no approved amendments yet.</p>
    </div>
    @endforelse
</div>
