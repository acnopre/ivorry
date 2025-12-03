<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 🔍 Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{ $this->form }}
        </div>

        {{-- 🧍 Member Results --}}
        @if($members->isNotEmpty())
        <div class="space-y-6">
            @foreach ($members as $member)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6 border-l-4 border-primary-500 space-y-4">

                {{-- 🧩 Member Header --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 mb-4 dark:border-gray-700">
                    <div class="space-y-1">
                        <div class="font-bold text-2xl text-gray-900 dark:text-white">{{ trim("{$member->first_name} " . ($member->middle_name ? substr($member->middle_name,0,1).'. ' : '') . "{$member->last_name}" . ($member->suffix ? ', '.$member->suffix : '')) }}
                        </div>
                        <div class="text-base text-gray-600 dark:text-gray-400">
                            <span class="font-semibold">Card #:</span> {{ $member->card_number }}
                        </div>
                    </div>

                    <x-filament::button color="primary" wire:click="openProcedureModal({{ $member->id }})" icon="heroicon-o-document-plus" class="mt-3 sm:mt-0">
                        Add Procedure
                    </x-filament::button>
                </div>

                {{-- 👤 Member Info --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Type</div>
                        <div class="text-gray-900 dark:text-white truncate">{{ $member->member_type }}</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Birthdate</div>
                        <div class="text-gray-900 dark:text-white truncate">{{ $member->birthdate }}</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Email</div>
                        <div class="text-gray-900 dark:text-white truncate">{{ $member->email }}</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Phone</div>
                        <div class="text-gray-900 dark:text-white truncate">{{ $member->phone }}</div>
                    </div>
                </div>

                {{-- 🏢 Account Info --}}
                @if($member->account)
                <div class="pt-6 mt-6 border-t dark:border-gray-700 space-y-4">
                    <h3 class="text-xl font-bold dark:text-primary-400 mb-4 flex items-center space-x-2">
                        {{-- Added Icon for visual identity --}}
                        <x-heroicon-o-building-office-2 class="w-5 h-5" />
                        <span> Account Information</span>
                    </h3>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="font-semibold text-gray-500 dark:text-gray-400">Company Name</div>
                            <div class="text-gray-900 dark:text-white text-base font-medium">{{ $member->account->company_name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-500 dark:text-gray-400">Policy Code</div>
                            {{-- Used font-mono for technical/code data for better distinction --}}
                            <div class="text-gray-900 dark:text-white text-base font-mono">{{ $member->account->policy_code ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-500 dark:text-gray-400">Status</div>

                            @php
                            $isActive = $member->account->account_status == 1;
                            @endphp

                            <x-filament::badge :color="$isActive ? 'success' : 'danger'" class="mt-1 inline-flex text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ $isActive ? 'Active' : 'Inactive' }}
                            </x-filament::badge>
                        </div>
                    </div>

                    {{-- 🧾 Services (From account_service pivot) --}}
                    <div class="mt-6">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3 pt-4 border-t dark:border-gray-700 flex items-center space-x-2">
                            <x-heroicon-o-list-bullet class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            <span>Covered Services</span>
                        </h4>

                        @if($member->account->services->isNotEmpty())
                        {{-- Enhanced table container styling to match Filament panels --}}
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
                                    @foreach($member->account->services as $service)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 fi-ta-row">
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $service->name }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($service->type) }}</td>
                                        <td class="px-4 py-3 font-mono"> {{ $service->pivot->quantity ?? '—' }}</td>
                                        {{-- Used icons instead of 'Yes'/'No' for quicker scanning --}}
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
                        {{-- Improved "No results" message with icon and better padding --}}
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
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Unit Type</th>
                                    {{-- <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Unit</th> --}}
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Approval Code</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                @foreach($procedures as $procedure)
                                @foreach($procedure->units as $unit)
                                @php
                                $statusClass = match($procedure->status) {
                                'approved' => 'bg-green-500/10 text-green-600 ring-green-500/20',
                                'denied' => 'bg-red-500/10 text-red-600 ring-red-500/20',
                                default => 'bg-yellow-500/10 text-yellow-600 ring-yellow-500/20',
                                };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $procedure->service->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $unit->unitType->name ?? '-' }}</td>
                                    {{-- <td class="px-4 py-2">{{ $unit->unit->name ?? '-' }}</td> --}}
                                    <td class="px-4 py-2">{{ $procedure->approval_code }}</td>
                                    {{-- <td class="px-4 py-2 font-mono">{{ $unit->quantity ?? '-' }}</td> --}}
                                    <td class="px-4 py-2">{{ $procedure->availment_date ? \Carbon\Carbon::parse($procedure->availment_date)->format('M d, Y') : '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                            {{ ucfirst($procedure->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <p class="text-gray-500 text-sm mt-4 italic">No procedures logged for this member yet.</p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        {{-- 🕵️ No Results --}}
        <div class="text-center text-gray-500 py-8 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-700">
            <svg class="w-8 h-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9A6 6 0 006 9c0 5 6 9 6 9s6-4 6-9zM10.5 9.75a.75.75 0 111.5 0 .75.75 0 01-1.5 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No member found</h3>
            <p class="mt-1 text-sm text-gray-500">Please contact HPDAI.</p>
        </div>
        @endif

        {{-- 🩹 Add Procedure Modal --}}
        <div x-data x-show="$wire.showProcedureModal" x-cloak x-trap.noscroll @click.away="$wire.showProcedureModal = false" x-on:keydown.escape.window="$wire.showProcedureModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-lg relative">
                <button @click="$wire.showProcedureModal = false" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h2 class="text-xl font-bold mb-4 dark:text-white">Add Procedure</h2>

                {{ $this->getProcedureForm() }}

                <div class="flex justify-end space-x-2 mt-6 pt-4 border-t dark:border-gray-700">
                    <x-filament::button color="secondary" wire:click="$set('showProcedureModal', false)">
                        Cancel
                    </x-filament::button>
                    <x-filament::button color="primary" wire:click="saveProcedure" icon="heroicon-o-check">
                        Save Procedure
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
    {{-- ✅ Approval Code Modal --}}
    <div x-data x-show="$wire.showApprovalModal" x-cloak x-trap.noscroll @click.away="$wire.showApprovalModal = false" x-on:keydown.escape.window="$wire.showApprovalModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-md relative text-center">

            <h2 class="text-2xl font-bold text-primary-600 mb-4">Procedure Approved</h2>

            <p class="text-gray-600 dark:text-gray-300 mb-3">
                The procedure has been approved successfully.<br>
                Here’s your unique approval code:
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
