<x-filament::page>
    <form wire:submit.prevent="submit" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit" class="w-full">
            Set Password
        </x-filament::button>
    </form>
</x-filament::page>
