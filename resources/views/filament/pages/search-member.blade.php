<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 🔍 Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{ $this->form }}
        </div>

        {{-- 🧍 Member Results --}}
        @if($members->isNotEmpty())
        <div class="space-y-4">
            @foreach ($members as $member)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden hover:shadow-md transition">
                <div class="p-6">
                    {{-- 🧩 Member Header --}}
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                                {{ trim("{$member->first_name} " . ($member->middle_name ? substr($member->middle_name,0,1).'. ' : '') . "{$member->last_name}" . ($member->suffix ? ', '.$member->suffix : '')) }}
                            </h3>
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <x-heroicon-o-identification class="w-4 h-4" />
                                <span class="font-medium">{{ $member->card_number }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <x-filament::button 
                                color="primary" 
                                wire:click="$set('selectedMemberId', {{ $member->id }})" 
                                x-on:click="$wire.mountAction('addProcedure')"
                                icon="heroicon-o-document-plus" 
                                :disabled="!$this->canAddProcedure($member)">
                                Add Procedure
                            </x-filament::button>
                            @if(!$this->canAddProcedure($member))
                            <div class="flex items-center gap-1 text-xs text-danger-600 dark:text-danger-400">
                                <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                                <span>{{ $this->getCanAddProcedureReason($member) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- 👤 Member Info --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $member->member_type }}</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</div>
                            @php
                            $memberStatus = strtolower($member->status ?? '');
                            $memberStatusColor = match ($memberStatus) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            };
                            @endphp
                            <x-filament::badge :color="$memberStatusColor" class="inline-flex">
                                {{ ucfirst($member->status ?? 'Unknown') }}
                            </x-filament::badge>
                        </div>
                        @if($member->inactive_date)
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Inactive Date</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">{{ \Carbon\Carbon::parse($member->inactive_date)->format('M d, Y') }}</div>
                        </div>
                        @endif
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Birthdate</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $member->birthdate }}</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium truncate">{{ $member->email }}</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $member->phone }}</div>
                        </div>
                    </div>

                    {{-- 🏢 Account Info --}}
                    @if($member->account)
                    <div class="border-t dark:border-gray-700 pt-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-building-office-2 class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            Account Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-building-office class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">Company</div>
                                        <div class="text-sm text-gray-900 dark:text-white font-semibold truncate">{{ $member->account->company_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-document-text class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-1">Policy Code</div>
                                        <div class="text-sm text-gray-900 dark:text-white font-mono font-semibold truncate">{{ $member->account->policy_code ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-shield-check class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide mb-1">Status</div>
                                        @php
                                        $status = $member->account->account_status;
                                        $color = match ($status) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'expired' => 'danger',
                                        default => 'gray',
                                        };
                                        @endphp
                                        <x-filament::badge :color="$color" size="md" class="inline-flex">
                                            {{ ucfirst($status) }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-lg p-4 border border-amber-200 dark:border-amber-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-heart class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1">HIP</div>
                                        <div class="text-sm text-gray-900 dark:text-white font-semibold truncate">{{ $member->account->hip ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Card Used</div>
                                <div class="text-sm text-gray-900 dark:text-white font-semibold">{{ $member->account->card_used ?? 'N/A' }}</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Plan Type</div>
                                <div class="text-sm text-gray-900 dark:text-white font-semibold">{{ ucfirst($member->account->plan_type ?? 'N/A') }}</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Coverage Period</div>
                                <div class="text-sm text-gray-900 dark:text-white font-semibold">{{ ucfirst($member->account->coverage_period_type ?? 'N/A') }}</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">MBL Type</div>
                                <div class="text-sm text-gray-900 dark:text-white font-semibold">{{ $member->account->mbl_type ?? 'N/A' }}</div>
                            </div>
                            @if($member->account->mbl_type === 'Fixed' && auth()->user()->hasRole('CSR'))
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">MBL Balance</div>
                                <div class="text-sm text-gray-900 dark:text-white font-semibold">₱{{ number_format($member->mbl_balance ?? 0, 2) }}</div>
                            </div>
                            @endif
                        </div>

                        {{-- Contract Dates --}}
                        @if(isset($member->effective_date) || isset($member->expiration_date) || isset($member->account->effective_date) || isset($member->account->expiration_date))
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 mt-6">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <x-heroicon-o-calendar class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                Contract Dates
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                @if(isset($member->effective_date))
                                <div class="flex items-center gap-2">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full bg-success-500"></div>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Member Effective</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->effective_date)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($member->expiration_date))
                                <div class="flex items-center gap-2">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full bg-danger-500"></div>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Member Expiration</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->expiration_date)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($member->account->effective_date))
                                <div class="flex items-center gap-2">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full bg-success-500"></div>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Account Effective</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->account->effective_date)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($member->account->expiration_date))
                                <div class="flex items-center gap-2">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full bg-danger-500"></div>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Account Expiration</div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($member->account->expiration_date)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- 🧾 Services --}}
                        <div class="mt-6">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3 pt-4 border-t dark:border-gray-700 flex items-center space-x-2">
                                <x-heroicon-o-list-bullet class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                <span>Covered Services</span>
                            </h4>

                            @php
                            $filteredServices = $member->account->services->filter(function($service) {
                            return $service->pivot->is_unlimited || ($service->pivot->quantity > 0);
                            });
                            @endphp
                            @if($filteredServices->isNotEmpty())
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 fi-ta-has-shadow">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm fi-ta-table">
                                    <thead class="bg-gray-50 dark:bg-gray-700 fi-ta-header">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Service Name</th>
                                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Type</th>
                                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Quantity</th>
                                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Unlimited</th>
                                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-600">

                                        @foreach($filteredServices as $service)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 fi-ta-row">
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $service->name }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($service->type) }}</td>
                                            <td class="px-4 py-3 font-mono"> {{ $service->pivot->quantity ?? '—' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                @if($service->pivot->is_unlimited)
                                                Yes
                                                @else
                                                No
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 italic">{{ $service->pivot->remarks ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-gray-500 italic p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                                <x-heroicon-o-information-circle class="w-4 h-4 inline mr-1 text-gray-400" />
                                No services assigned to this account.
                            </p>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- 🩺 Procedures --}}
                    @php
                    $procedures = \App\Models\Procedure::with(['units.unitType', 'service'])
                    ->where('member_id', $member->id)
                    ->orderByDesc('availment_date')
                    ->get();
                    @endphp
                    @if($procedures->isNotEmpty())
                    <div class="pt-4 mt-6 border-t dark:border-gray-700">
                        <h3 class="text-xl font-semibold mb-4 dark:text-white">Recent Procedures</h3>
                        <div class="w-full overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Service</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Units</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Approval Code</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                    @foreach($procedures as $procedure)
                                    @php
                                    $statusClass = match($procedure->status) {
                                    'approved' => 'bg-green-500/10 text-green-600 ring-green-500/20',
                                    'denied' => 'bg-red-500/10 text-red-600 ring-red-500/20',
                                    default => 'bg-yellow-500/10 text-yellow-600 ring-yellow-500/20',
                                    };
                                    @endphp
                                    @if($procedure->units->isEmpty())
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $procedure->service->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-gray-500 italic">No units</td>
                                        <td class="px-4 py-2">{{ $procedure->approval_code }}</td>
                                        <td class="px-4 py-2">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '-' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                                {{ ucfirst($procedure->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @else
                                    @foreach($procedure->units as $unit)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $procedure->service->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2">
                                            @if($unit->pivot->surface_id)
                                            {{ $unit->unitType->name ?? '-' }}: {{ \App\Models\Unit::find($unit->pivot->unit_id)?->name ?? '-' }} | Surface: {{ $unit->name ?? '-' }}
                                            @else
                                            {{ $unit->unitType->name ?? '-' }}: {{ $unit->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">{{ $procedure->approval_code }}</td>
                                        <td class="px-4 py-2">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '-' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                                {{ ucfirst($procedure->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="pt-4 mt-6 border-t dark:border-gray-700">
                        <p class="text-gray-500 italic p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <x-heroicon-o-information-circle class="w-4 h-4 inline mr-1 text-gray-400" />
                            No procedures logged for this member yet.
                        </p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-gray-500 py-8 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-700">
                <svg class="w-8 h-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9A6 6 0 006 9c0 5 6 9 6 9s6-4 6-9zM10.5 9.75a.75.75 0 111.5 0 .75.75 0 01-1.5 0z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No member found</h3>
                <p class="mt-1 text-sm text-gray-500">Please contact HPDAI.</p>
            </div>
            @endif
        </div>

        <x-filament-actions::modals />

        {{-- Approval Code Modal --}}
        <x-filament::modal id="approval-code" width="md">
            <x-slot name="heading">
                Procedure Approved
            </x-slot>

            <div class="text-center">
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
            </div>

            <x-slot name="footer">
                <x-filament::button class="ml-auto" color="primary" wire:click="$dispatch('close-modal', { id: 'approval-code' })">
                    Close
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
</x-filament-panels::page>
