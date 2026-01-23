<x-filament-panels::page>
    {{-- Loading indicator --}}
    <div wire:loading.delay wire:target="$wire" class="p-4 flex items-center space-x-2 bg-gray-50 rounded">
        <x-filament::loading-indicator class="h-5 w-5" />
        <span class="text-sm text-gray-600">Generating report…</span>
    </div>

    <div class="space-y-6 mt-4">
        {{-- Filters Form --}}
        {{ $this->form }}

        {{-- Reports Table --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
