<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- Member Results --}}
        @if($hasSearched)
        @if($members->isNotEmpty())
        <div class="flex flex-col gap-6">
            @foreach ($members as $member)
            @php
            $canAdd = $this->canAddProcedure($member);
            $reason = $this->getCanAddProcedureReason($member);
            $memberStatus = strtolower($member->status ?? '');
            $memberStatusColor = match($memberStatus) {
            'active' => 'success',
            'inactive' => 'danger',
            default => 'gray',
            };
            @endphp

            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

                {{-- Card Header --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
                                {{ trim("{$member->first_name} " . ($member->middle_name ? substr($member->middle_name,0,1).'. ' : '') . "{$member->last_name}" . ($member->suffix ? ', '.$member->suffix : '')) }}
                            </h3>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-identification class="w-3.5 h-3.5" />
                                    {{ $member->card_number }}
                                </span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $member->member_type }}</span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <x-filament::badge :color="$memberStatusColor" size="sm" class="!w-auto">{{ ucfirst($member->status ?? 'Unknown') }}</x-filament::badge>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-start sm:items-end gap-1">
                        <x-filament::button color="primary" wire:click="$set('selectedMemberId', {{ $member->id }})" x-on:click="$wire.mountAction('addProcedure')" icon="heroicon-o-document-plus" size="sm" :disabled="!$canAdd">
                            Add Procedure
                        </x-filament::button>
                        @if(!$canAdd)
                        <div class="flex items-center gap-1 text-xs text-danger-600 dark:text-danger-400">
                            <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5" />
                            <span>{{ $reason }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Member Details --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Birthdate</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $member->birthdate ? \Carbon\Carbon::parse($member->birthdate)->format('M d, Y') : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Gender</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->gender ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Email</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $member->email ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Phone</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->phone ?? '—' }}</div>
                        </div>
                        @if($member->inactive_date)
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Inactive Date</div>
                            <div class="text-sm font-medium text-red-600 dark:text-red-400">{{ \Carbon\Carbon::parse($member->inactive_date)->format('M d, Y') }}</div>
                        </div>
                        @endif
                        @if($member->account?->mbl_type === 'Fixed' && auth()->user()->hasRole('CSR'))
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">MBL Balance</div>
                            <div class="text-sm font-bold text-primary-600 dark:text-primary-400">₱{{ number_format($member->mbl_balance ?? 0, 2) }}</div>
                        </div>
                        @endif
                    </div>

                    {{-- Account Info --}}
                    @if($member->account)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                            <x-heroicon-o-building-office-2 class="w-4 h-4 text-primary-500" />
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Account</span>
                        </div>
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Company</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $member->account->company_name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Policy Code</div>
                                <div class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $member->account->policy_code ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Status</div>
                                @php
                                $accStatus = $member->account->account_status;
                                $accColor = match($accStatus) {
                                'active' => 'success', 'inactive' => 'warning', 'expired' => 'danger', default => 'gray',
                                };
                                @endphp
                                <x-filament::badge :color="$accColor" size="sm">{{ ucfirst($accStatus) }}</x-filament::badge>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Plan Type</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $member->account->plan_type ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">MBL Type</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $member->account->mbl_type ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">HIP</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $member->account->hip ?? '—' }}</div>
                            </div>
                        </div>

                        {{-- Coverage Dates --}}
                        @if($member->effective_date || $member->expiration_date || $member->account->effective_date || $member->account->expiration_date)
                        <div class="px-4 pb-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @if($member->effective_date)
                            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 px-3 py-2">
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-0.5">Member Effective</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->effective_date)->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($member->expiration_date)
                            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 px-3 py-2">
                                <div class="text-xs text-red-600 dark:text-red-400 font-medium mb-0.5">Member Expiration</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->expiration_date)->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($member->account->effective_date)
                            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 px-3 py-2">
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-0.5">Account Effective</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->account->effective_date)->format('M d, Y') }}</div>
                            </div>
                            @endif
                            @if($member->account->expiration_date)
                            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 px-3 py-2">
                                <div class="text-xs text-red-600 dark:text-red-400 font-medium mb-0.5">Account Expiration</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->account->expiration_date)->format('M d, Y') }}</div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Covered Services --}}
                    @if($member->account)
                    @php
                    $filteredServices = $member->account->services->filter(fn($s) => $s->pivot->is_unlimited || $s->pivot->quantity > 0);
                    @endphp
                    @if($filteredServices->isNotEmpty())
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                            <x-heroicon-o-list-bullet class="w-4 h-4 text-primary-500" />
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Covered Services</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Service</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Qty</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Unlimited</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($filteredServices as $service)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">{{ $service->name }}</td>
                                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ ucfirst($service->type) }}</td>
                                        <td class="px-4 py-2.5 font-mono text-gray-700 dark:text-gray-300">{{ $service->pivot->quantity ?? '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            @if($service->pivot->is_unlimited)
                                            <x-filament::badge color="success" size="sm">Yes</x-filament::badge>
                                            @else
                                            <x-filament::badge color="gray" size="sm">No</x-filament::badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 italic text-xs">{{ $service->pivot->remarks ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                    @endif

                    {{-- Procedures --}}
                    @php
                    $procedures = \App\Models\Procedure::with(['units.unitType', 'service'])
                    ->where('member_id', $member->id)
                    ->orderByDesc('availment_date')
                    ->get();
                    @endphp
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-primary-500" />
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Procedures</span>
                            <span class="ml-auto text-xs text-gray-400">{{ $procedures->count() }} record(s)</span>
                        </div>
                        @if($procedures->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Service</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Units</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Approval Code</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                                        <th class="px-4 py-2.5"></th>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($procedures as $procedure)
                                    @php
                                    $procColor = match($procedure->status) {
                                    'approved' => 'success',
                                    'denied' => 'danger',
                                    'valid' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                    };
                                    @endphp
                                    @if($procedure->units->isEmpty())
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">{{ $procedure->service->name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-gray-400 italic text-xs">—</td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $procedure->approval_code }}</td>
                                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <x-filament::badge :color="$procColor" size="sm">{{ ucfirst($procedure->status) }}</x-filament::badge>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if($procedure->status === 'pending')
                                            <x-filament::button color="danger" size="xs" wire:click="openCancelModal({{ $procedure->id }})">
                                                Cancel
                                            </x-filament::button>
                                            @endif
                                        </td>
                                    </tr>
                                    @else
                                    @foreach($procedure->units as $unit)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">{{ $procedure->service->name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300">
                                            @if($unit->pivot->surface_id)
                                            {{ $unit->unitType->name ?? '—' }}: {{ \App\Models\Unit::find($unit->pivot->unit_id)?->name ?? '—' }} / {{ $unit->name ?? '—' }}
                                            @else
                                            {{ $unit->unitType->name ?? '—' }}: {{ $unit->name ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $procedure->approval_code }}</td>
                                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <x-filament::badge :color="$procColor" size="sm">{{ ucfirst($procedure->status) }}</x-filament::badge>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if($procedure->status === 'pending')
                                            <x-filament::button color="danger" size="xs" wire:click="openCancelModal({{ $procedure->id }})">
                                                Cancel
                                            </x-filament::button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500 italic">
                            No procedures logged yet.
                        </div>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-2xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">No member found</h3>
            <p class="mt-1 text-sm text-gray-400">Please contact HPDAI.</p>
        </div>
        @endif
        @endif
    </div>

    <x-filament-actions::modals />

    {{-- Cancel Procedure Modal --}}
    <div x-data x-show="$wire.showCancelModal" x-cloak x-trap.noscroll @click.away="$wire.showCancelModal = false" x-on:keydown.escape.window="$wire.showCancelModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-md relative">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Cancel Procedure</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Please provide a reason for cancelling this procedure.</p>
            <textarea
                wire:model="cancelReason"
                rows="3"
                placeholder="Enter reason..."
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
            ></textarea>
            <div class="flex justify-end gap-3 mt-4">
                <x-filament::button color="gray" wire:click="$set('showCancelModal', false)">Dismiss</x-filament::button>
                <x-filament::button color="danger" wire:click="confirmCancelProcedure">Confirm Cancel</x-filament::button>
            </div>
        </div>
    </div>

    {{-- ✅ Approval Code Modal --}}
    <div x-data x-show="$wire.showApprovalModal" x-cloak x-trap.noscroll @click.away="$wire.showApprovalModal = false" x-on:keydown.escape.window="$wire.showApprovalModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-md relative text-center">

            <h2 class="text-2xl font-bold text-primary-600 mb-4">Procedure Approved</h2>

            <p class="text-gray-600 dark:text-gray-300 mb-3">
                The procedure has been approved successfully.<br>
                Here's your unique approval code:
            </p>

            <div class="bg-primary-50 dark:bg-primary-900/30 rounded-lg py-4 px-6 mt-3">
                <span class="text-3xl font-extrabold tracking-widest text-primary-700 dark:text-primary-400">
                    {{ $approvalCode }}
                </span>
            </div>

            <p class="text-xs text-gray-500 mt-3">
                Please provide this code to the member for reference and verification.
            </p>

            <div class="mt-6">
                <x-filament::button color="primary" wire:click="$set('showApprovalModal', false)">
                    Close
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
