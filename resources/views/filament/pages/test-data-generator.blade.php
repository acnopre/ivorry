<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Test Data Generator</x-slot>
        <x-slot name="description">Generate sample XLS files for import testing.</x-slot>

        {{ $this->form }}

        <div class="flex flex-wrap gap-3 mt-6">
            <x-filament::button
                wire:click="generateBoth"
                color="primary"
                icon="heroicon-o-archive-box-arrow-down"
            >
                Download Both (ZIP)
            </x-filament::button>

            <x-filament::button
                wire:click="generateAccounts"
                color="info"
                icon="heroicon-o-building-office"
            >
                Accounts Only
            </x-filament::button>

            <x-filament::button
                wire:click="generateClinics"
                color="warning"
                icon="heroicon-o-building-storefront"
            >
                Clinics Only
            </x-filament::button>

            <x-filament::button
                wire:click="generateMembers"
                color="success"
                icon="heroicon-o-users"
            >
                Members from DB
            </x-filament::button>
        </div>

        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 space-y-1">
            <p><strong>Download Both</strong> — generates accounts + members linked to those accounts in a ZIP.</p>
            <p><strong>Accounts Only</strong> — generates accounts XLS only.</p>
            <p><strong>Clinics Only</strong> — generates clinics XLS with <code>account_name</code> and <code>hip_name</code> columns (use exact names from DB).</p>
            <p><strong>Members from DB</strong> — generates members linked to existing accounts in the database.</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
