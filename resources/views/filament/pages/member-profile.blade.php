<x-filament-panels::page>
    @if(auth()->user()->member)
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save Changes
            </x-filament::button>
        </div>
    </form>
    @else
    <x-filament::section>
        <div class="text-center py-8">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-12 h-12 mx-auto text-warning-500 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Member Profile Found</h3>
            <p class="text-gray-600 dark:text-gray-400">Your account is not linked to a member profile. Please contact support for assistance.</p>
        </div>
    </x-filament::section>
    @endif
</x-filament-panels::page>
