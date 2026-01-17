<x-filament::page>
    <x-slot name="header">
        <h2 class="text-xl font-bold tracking-tight">
            Service Fee Approval
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            Review all clinics with pending service fee approvals. Approve fees to update current rates.
        </p>
    </x-slot>

    <div class="mt-6">
        {{-- Filament table --}}
        {{ $this->table }}
    </div>
</x-filament::page>
