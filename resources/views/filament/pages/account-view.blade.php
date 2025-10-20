<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.list>
            <x-filament::tabs.item :active="$activeTab === 'info'" wire:click="$set('activeTab', 'info')">
                Account Info
            </x-filament::tabs.item>

            <x-filament::tabs.item :active="$activeTab === 'claims'" wire:click="$set('activeTab', 'claims')">
                Claims
            </x-filament::tabs.item>

            <x-filament::tabs.item :active="$activeTab === 'services'" wire:click="$set('activeTab', 'services')">
                Services
            </x-filament::tabs.item>
        </x-filament::tabs.list>

        <x-filament::tabs.content>
            @if ($activeTab === 'info')
            <x-filament::section heading="Account Information">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold text-gray-700">Company Name</dt>
                        <dd>{{ $account->company_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Policy Code</dt>
                        <dd>{{ $account->policy_code }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Effective Date</dt>
                        <dd>{{ \Carbon\Carbon::parse($account->effective_date)->format('F d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Expiration Date</dt>
                        <dd>{{ \Carbon\Carbon::parse($account->expiration_date)->format('F d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-700">Status</dt>
                        <dd>{{ ucfirst($account->status) }}</dd>
                    </div>
                </dl>
            </x-filament::section>
            @elseif ($activeTab === 'claims')
            <x-filament::section heading="Claims">
                <livewire:claims-table :accountId="$account->id" />
            </x-filament::section>
            @elseif ($activeTab === 'services')
            <x-filament::section heading="Services">
                {{ $this->table }}
            </x-filament::section>
            @endif
        </x-filament::tabs.content>
    </x-filament::tabs>
</x-filament-panels::page>
