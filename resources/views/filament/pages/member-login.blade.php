<x-filament-panels::page.simple>
    <p class="text-gray-600 dark:text-gray-300 text-sm mt-2">
        Enter your credentials below
    </p>
    {{ $this->form }}

    <x-filament::button wire:click="submit" class="w-full bg-primary-600 hover:bg-primary-700">
        Login
    </x-filament::button>
</x-filament-panels::page.simple>
