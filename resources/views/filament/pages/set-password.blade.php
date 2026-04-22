
<x-filament-panels::page.simple>
    <p class="text-gray-600 dark:text-gray-300 text-sm mt-2">
        Enter your new password below
    </p>

    {{ $this->form }}

    <x-filament::button
        wire:click="submit"
        color="primary"
        class="w-full"
    >
        Save Password
    </x-filament::button>
</x-filament-panels::page.simple>
