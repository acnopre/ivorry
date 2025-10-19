<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                System Overview
            </x-slot>
            <livewire:filament.widgets.dashboard-stats />
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Recent Claims
            </x-slot>
            <livewire:filament.widgets.recent-claims-table />
        </x-filament::section>
    </div>
</x-filament-panels::page>
