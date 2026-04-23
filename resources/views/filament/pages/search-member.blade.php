<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- No Results --}}
        @if($hasSearched && $members->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <x-heroicon-o-user-minus class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No member found</p>
            <p class="text-xs text-gray-400 mt-1">Try a different name or card number.</p>
        </div>
        @endif

        {{-- Member Cards --}}
        @if($hasSearched && $members->isNotEmpty())
        @foreach ($members as $member)
        @php
        $canAdd = $this->canAddProcedure($member);
        $reason = $this->getCanAddProcedureReason($member);
        $statusColor = match(strtolower($member->status ?? '')) { 'active' => 'success', 'inactive' => 'danger', default => 'gray' };
        $accStatus = $member->account?->account_status;
        $accColor = match($accStatus) { 'active' => 'success', 'expired' => 'danger', default => 'gray' };
        $procedures = \App\Models\Procedure::with(['units.unitType', 'service', 'clinic'])
        ->where('member_id', $member->id)
        ->when(auth()->user()->hasRole('Dentist'), fn($q) => $q->whereHas('service', fn($s) => $s->where('type', '!=', 'special')))
        ->orderByDesc('updated_at')
        ->get();
        $isShared = strtoupper($member->account?->plan_type ?? '') === 'SHARED';
        if ($member->card_number) {
            \App\Models\MemberService::initializeForCard($member->card_number, $member->account->id);
            $filteredServices = \App\Models\MemberService::where('card_number', $member->card_number)
                ->where('account_id', $member->account->id)
                ->with('service')
                ->get()
                ->filter(fn($ms) => !( auth()->user()->hasRole('Dentist') && $ms->service?->type === 'special'));
        } else {
            $filteredServices = collect();
        }
        $isCsr = auth()->user()->hasRole('CSR');
        $isDentistUser = auth()->user()->hasRole('Dentist');
        $userClinicId = auth()->user()->clinic?->id;
        $defaultOpen = $isCsr ? $loop->first : $member->member_type === 'PRINCIPAL';
        @endphp

        <div x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

            {{-- ── Header ── --}}
            <div class="fi-section-header flex flex-col gap-y-1 px-6 py-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 dark:border-white/10">
                <div class="flex items-center gap-x-3 min-w-0">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-primary-50 dark:bg-primary-900/30 shrink-0">
                        <x-heroicon-o-user class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ trim("{$member->first_name} " . ($member->middle_name ? substr($member->middle_name,0,1).'. ' : '') . "{$member->last_name}" . ($member->suffix ? ', '.$member->suffix : '')) }}
                        </p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                            <span class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $member->card_number }}</span>
                            <span class="text-gray-300 dark:text-gray-600">·</span>
                            <span class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">{{ $member->member_type }}</span>
                            <span class="text-gray-300 dark:text-gray-600">·</span>
                            <x-filament::badge :color="$statusColor" size="sm">{{ ucfirst($member->status ?? 'Unknown') }}</x-filament::badge>
                        </div>
                    </div>
                </div>
                @if($isCsr || $isDentistUser)
                <button type="button" x-on:click="open = !open" class="ml-auto p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <x-heroicon-o-chevron-down class="w-4 h-4 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                </button>
                @endif
                <div class="flex flex-col items-start gap-y-1 sm:items-end shrink-0">
                    <x-filament::button color="primary" size="sm" icon="heroicon-o-plus" :disabled="!$canAdd" wire:click="openAddProcedure({{ $member->id }})">
                        Add Procedure
                    </x-filament::button>
                    @if(!$canAdd)
                    <p class="text-sm text-danger-600 dark:text-danger-400 flex items-center gap-x-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        {{ $reason }}
                    </p>
                    @endif
                </div>
            </div>

            <div x-show="open" x-collapse>
                {{-- ── Member Info ── --}}
                <div class="fi-section-content px-6 py-4 border-b border-gray-200 dark:border-white/10">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-4 lg:grid-cols-6 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Birthdate</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $member->birthdate ? \Carbon\Carbon::parse($member->birthdate)->format('M d, Y') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Gender</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $member->gender ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white truncate">{{ $member->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $member->phone ?? '—' }}</dd>
                        </div>
                        @if($member->inactive_date)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Inactive Date</dt>
                            <dd class="mt-1 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ \Carbon\Carbon::parse($member->inactive_date)->format('M d, Y') }}</dd>
                        </div>
                        @endif
                        @if($member->account?->mbl_type === 'Fixed' && $isCsr)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">MBL Balance</dt>
                            <dd class="mt-1 text-sm font-semibold text-success-600 dark:text-success-400">₱{{ number_format($member->mbl_balance ?? 0, 2) }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <div class="fi-section-content px-6 py-4 space-y-4">

                    {{-- ── Account ── --}}
                    @if($member->account)
                    <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                        <div class="flex items-center gap-x-2 px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                            <x-heroicon-o-building-office-2 class="w-4 h-4 text-primary-500" />
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">Account</span>
                        </div>
                        <div class="px-4 py-3">
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 lg:grid-cols-6 text-sm">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Company</dt>
                                    <dd class="mt-1 font-medium text-gray-950 dark:text-white truncate">{{ $member->account->company_name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Policy Code</dt>
                                    <dd class="mt-1 font-mono font-semibold text-primary-600 dark:text-primary-400">{{ $member->account->policy_code ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1 inline-flex">
                                        <x-filament::badge :color="$accColor" size="sm">{{ ucfirst($accStatus ?? '—') }}</x-filament::badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Plan Type</dt>
                                    <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $member->account->plan_type ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">MBL Type</dt>
                                    <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $member->account->mbl_type ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">HIP</dt>
                                    <dd class="mt-1 font-medium text-gray-950 dark:text-white truncate">{{ $member->account->hip->name ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Coverage Dates --}}
                        @php
                        $hasMemberDates = $member->effective_date || $member->expiration_date;
                        $hasAccountDates = $member->account->effective_date || $member->account->expiration_date;
                        @endphp
                        @if($hasMemberDates || $hasAccountDates)
                        <div class="px-4 pb-3 pt-3 border-t border-gray-200 dark:border-white/10">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @if($hasMemberDates)
                                <div class="flex items-center gap-x-3 rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                    <x-heroicon-o-user class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                        @if($member->effective_date)
                                        <div>
                                            <span class="text-gray-400 dark:text-gray-500">Effective</span>
                                            <span class="ml-1 font-semibold text-success-600 dark:text-success-400">{{ \Carbon\Carbon::parse($member->effective_date)->format('M d, Y') }}</span>
                                        </div>
                                        @endif
                                        @if($member->expiration_date)
                                        <div>
                                            <span class="text-gray-400 dark:text-gray-500">Expires</span>
                                            <span class="ml-1 font-semibold text-danger-600 dark:text-danger-400">{{ \Carbon\Carbon::parse($member->expiration_date)->format('M d, Y') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    <span class="ml-auto text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide shrink-0">Member</span>
                                </div>
                                @endif
                                @if($hasAccountDates)
                                <div class="flex items-center gap-x-3 rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                        @if($member->account->effective_date)
                                        <div>
                                            <span class="text-gray-400 dark:text-gray-500">Effective</span>
                                            <span class="ml-1 font-semibold text-success-600 dark:text-success-400">{{ \Carbon\Carbon::parse($member->account->effective_date)->format('M d, Y') }}</span>
                                        </div>
                                        @endif
                                        @if($member->account->expiration_date)
                                        <div>
                                            <span class="text-gray-400 dark:text-gray-500">Expires</span>
                                            <span class="ml-1 font-semibold text-danger-600 dark:text-danger-400">{{ \Carbon\Carbon::parse($member->account->expiration_date)->format('M d, Y') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    <span class="ml-auto text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide shrink-0">Account</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- ── Covered Services ── --}}
                    @if($filteredServices->isNotEmpty())
                    <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                        <div class="flex items-center gap-x-2 px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                            <x-heroicon-o-list-bullet class="w-4 h-4 text-primary-500" />
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">Covered Services</span>
                            @if($isShared)
                            <x-filament::badge color="info" size="sm">Family</x-filament::badge>
                            @endif
                            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">{{ $filteredServices->count() }} service(s)</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Service</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($filteredServices as $item)
                                    @php
                                        $svc = $item->service;
                                        $qty = $item->quantity;
                                        $defaultQty = $item->default_quantity;
                                        $unlimited = $item->is_unlimited;
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-100">{{ $svc->name ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ ($svc->type ?? '') === 'special' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' : 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-400' }}">
                                                {{ ucfirst($svc->type ?? '') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 font-mono font-semibold">
                                            @if($unlimited)
                                                <x-filament::badge color="success" size="sm">Unlimited</x-filament::badge>
                                            @elseif($qty == 0)
                                                <span class="text-danger-600 dark:text-danger-400">{{ $qty }}/{{ $defaultQty }}</span>
                                            @else
                                                {{ $qty }}/{{ $defaultQty }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(auth()->user()->hasRole('Dentist') && $member->account?->services?->contains(fn($s) => $s->type === 'special'))
                        <div class="px-4 py-2 border-t border-amber-100 dark:border-amber-800/40 bg-amber-50 dark:bg-amber-900/10 flex items-center gap-2">
                            <x-heroicon-o-phone class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                            <span class="text-xs text-amber-600 dark:text-amber-400">Special services included. Call HPDAI for details.</span>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- ── Procedures ── --}}
                    <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                        <div class="flex items-center gap-x-2 px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-primary-500" />
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">Procedures</span>
                            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">{{ $procedures->count() }} record(s)</span>
                        </div>
                        @if($procedures->isNotEmpty())
                        @php
                        $isDentist = auth()->user()->hasRole('Dentist');
                        $hasOwnProcedure = $isDentist && $procedures->contains(fn($p) => $userClinicId && $userClinicId === $p->clinic_id);
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Service</th>
                                        @if($isCsr)
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Clinic</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Approval Code</th>
                                        @endif
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Units</th>
                                        @if($isCsr || $hasOwnProcedure)
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                                        @endif
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($procedures as $procedure)
                                    @php
                                    $procColor = match($procedure->status) {
                                    'valid' => 'success',
                                    'signed' => 'info',
                                    'cancelled' => 'danger',
                                    'invalid' => 'danger',
                                    'processed' => 'gray',
                                    default => 'warning',
                                    };
                                    $isOwnProcedure = $userClinicId && $userClinicId === $procedure->clinic_id;
                                    $showCancel = $isCsr
                                        ? in_array($procedure->status, ['pending', 'signed'])
                                        : ($procedure->status === 'pending' && $isOwnProcedure);
                                    $rows = $procedure->units->isEmpty() ? [null] : $procedure->units;
                                    @endphp
                                    @foreach($rows as $unit)
                                    <tr class="hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-colors">
                                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-100">{{ $procedure->service->name ?? '—' }}</td>
                                        @if($isCsr)
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $procedure->clinic->clinic_name ?? '—' }}</td>
                                        <td class="px-4 py-2 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $procedure->approval_code }}</td>
                                        @endif
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '—' }}</td>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                            @if($unit === null) <span class="text-gray-300 dark:text-gray-600 italic">—</span>
                                            @elseif($unit->pivot->surface_id) {{ $unit->unitType->name ?? '—' }}: {{ \App\Models\Unit::find($unit->pivot->unit_id)?->name ?? '—' }} / {{ $unit->name ?? '—' }}
                                            @else {{ $unit->unitType->name ?? '—' }}: {{ $unit->name ?? '—' }}
                                            @endif
                                        </td>
                                        @if($isCsr || ($isDentist && $isOwnProcedure))
                                        <td class="px-4 py-2">
                                            <x-filament::badge :color="$procColor" size="sm">{{ ucfirst($procedure->status) }}</x-filament::badge>
                                        </td>
                                        @endif
                                        <td class="px-4 py-2">
                                            @if($showCancel)
                                            <x-filament::button color="danger" size="xs" wire:click="openCancelModal({{ $procedure->id }})">Cancel</x-filament::button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="flex flex-col items-center justify-center py-8 px-6">
                            <x-heroicon-o-clipboard-document class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No procedures logged yet.</p>
                        </div>
                        @endif
                    </div>

                </div>
            </div>{{-- /x-show --}}
        </div>
        @endforeach
        @endif
    </div>

    <x-filament-actions::modals />

    {{-- Cancel Modal --}}
    <div x-data x-show="$wire.showCancelModal" x-cloak x-trap.noscroll @click.away="$wire.showCancelModal = false" x-on:keydown.escape.window="$wire.showCancelModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-md">
            <div class="flex items-center gap-2 mb-1">
                <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500 shrink-0" />
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Cancel Procedure</h2>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Please provide a reason for cancelling this procedure.</p>
            <textarea wire:model="cancelReason" rows="3" placeholder="Enter reason..." class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
            <div class="flex justify-end gap-2 mt-4">
                <x-filament::button color="gray" wire:click="$set('showCancelModal', false)">Dismiss</x-filament::button>
                <x-filament::button color="danger" wire:click="confirmCancelProcedure">Confirm Cancel</x-filament::button>
            </div>
        </div>
    </div>

    {{-- Approval Code Modal --}}
    <div x-data x-show="$wire.showApprovalModal" x-cloak x-trap.noscroll @click.away="$wire.showApprovalModal = false" x-on:keydown.escape.window="$wire.showApprovalModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-sm text-center">
            <x-heroicon-o-check-circle class="w-10 h-10 text-primary-500 mx-auto mb-3" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Procedure Approved</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Share this approval code with the member.</p>

            <div x-data="{ copied: false }" class="bg-primary-50 dark:bg-primary-900/30 rounded-lg py-4 px-6 flex items-center justify-center gap-3 cursor-pointer hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors" x-on:click="navigator.clipboard.writeText('{{ $approvalCode }}'); copied = true; setTimeout(() => copied = false, 2000)">
                <span class="text-2xl font-extrabold tracking-widest text-primary-700 dark:text-primary-300">{{ $approvalCode }}</span>
                <x-heroicon-o-clipboard-document class="w-4 h-4 text-primary-400" x-show="!copied" />
                <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-emerald-500" x-show="copied" x-cloak />
            </div>
            <p class="text-[10px] text-gray-400 mt-2">Click the code to copy to clipboard.</p>

            <div class="mt-5">
                <x-filament::button color="primary" wire:click="$set('showApprovalModal', false)">Close</x-filament::button>
            </div>
        </div>
    </div>

</x-filament-panels::page>
