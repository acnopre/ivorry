@php
    $member     = $getRecord();
    $cardNumber = $member->card_number ?? $member->coc_number;

    $relatedMembers = \App\Models\Member::with(['account.hip'])
        ->where('card_number', $cardNumber)
        ->whereNotNull('card_number')
        ->get()
        ->groupBy('account_id');

    $accountIds = $relatedMembers->keys()->toArray();
    $firstId    = $accountIds[0] ?? null;
@endphp

@if($relatedMembers->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">No related accounts found for this card number.</p>
@else
    <div x-data="{ activeTab: '{{ $firstId }}' }">

        {{-- Tab Headers --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Account Tabs">
                @foreach($relatedMembers as $accountId => $members)
                    @php
                        $account   = $members->first()->account;
                        $isCurrent = $accountId == $member->account_id;
                        $statusColor = match($account?->account_status) {
                            'active'   => 'text-success-600 dark:text-success-400',
                            'expired'  => 'text-danger-600 dark:text-danger-400',
                            'inactive' => 'text-warning-600 dark:text-warning-400',
                            default    => 'text-gray-400',
                        };
                    @endphp
                    <button
                        type="button"
                        @click="activeTab = '{{ $accountId }}'"
                        :class="activeTab === '{{ $accountId }}'
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-150 flex items-center gap-2"
                    >
                        {{ $account?->company_name ?? 'Unknown' }}
                        @if($isCurrent)
                            <x-filament::badge color="primary" size="sm">Current</x-filament::badge>
                        @endif
                        <span class="text-xs {{ $statusColor }}">{{ ucfirst($account?->account_status ?? '') }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Panels --}}
        @foreach($relatedMembers as $accountId => $members)
            @php
                $account = $members->first()->account;
                if (!$account) continue;

                $memberServices = \App\Models\MemberService::where('card_number', $cardNumber)
                    ->where('account_id', $accountId)
                    ->with('service')
                    ->get()
                    ->filter(fn($ms) => $ms->is_unlimited || ($ms->default_quantity ?? 0) > 0);

                $accountServiceRemarks = \App\Models\AccountService::where('account_id', $accountId)
                    ->pluck('remarks', 'service_id');
            @endphp

            <div x-show="activeTab === '{{ $accountId }}'" x-cloak class="py-4 space-y-4">

                {{-- Account Info --}}
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Policy Code</dt>
                        <dd class="mt-1 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $account->policy_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">HIP</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $account->hip?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Plan Type</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $account->plan_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">MBL Type</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $account->mbl_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Effective</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $account->effective_date ? \Carbon\Carbon::parse($account->effective_date)->format('M d, Y') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Valid Until</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $account->expiration_date ? \Carbon\Carbon::parse($account->expiration_date)->format('M d, Y') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            <x-filament::badge :color="match($account->account_status) { 'active' => 'success', 'expired' => 'danger', 'inactive' => 'warning', default => 'gray' }" size="sm">
                                {{ ucfirst($account->account_status) }}
                            </x-filament::badge>
                        </dd>
                    </div>
                </dl>

                {{-- Members --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Members on this card</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($members as $m)
                            <x-filament::badge :color="$m->id === $member->id ? 'primary' : 'gray'" size="sm">
                                {{ $m->first_name }} {{ $m->last_name }} · {{ $m->member_type }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                </div>

                {{-- Services --}}
                @if($memberServices->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No services initialized for this account.</p>
                @else
                    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                        <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Service</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Balance / Default</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                                @foreach($memberServices as $ms)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $ms->service->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center font-mono">
                                            @if($ms->is_unlimited)
                                                <x-filament::badge color="success" size="sm">Unlimited</x-filament::badge>
                                            @elseif($ms->quantity == 0)
                                                <x-filament::badge color="danger" size="sm">0 / {{ $ms->default_quantity }}</x-filament::badge>
                                            @elseif($ms->quantity < ($ms->default_quantity * 0.25))
                                                <x-filament::badge color="warning" size="sm">{{ $ms->quantity }} / {{ $ms->default_quantity }}</x-filament::badge>
                                            @else
                                                <span class="text-gray-700 dark:text-gray-300">{{ $ms->quantity }} / {{ $ms->default_quantity }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $accountServiceRemarks[$ms->service_id] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
