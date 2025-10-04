<x-filament-panels::page.simple>
    <p class="text-gray-600 text-sm mt-2">Please provide a valid email to continue using the system.</p>
    {{ $this->form }}

    <x-filament::button wire:click="save" class="w-full bg-primary-600 hover:bg-primary-700">
        Update Email
    </x-filament::button>
</x-filament-panels::page.simple>
