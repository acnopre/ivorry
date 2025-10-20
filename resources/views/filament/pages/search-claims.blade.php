<x-filament-panels::page>
    <div class="p-6 space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        @if ($this->hasSearched)
        {{ $this->table }}
        @else
        <div class="fi-section text-center py-10 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <br>
            <x-heroicon-o-magnifying-glass class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-500" />

            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Start Searching</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Enter your criteria above and click 'Search Claims' to view results.
            </p>
            <br>

        </div>
        @endif
    </div>
</x-filament-panels::page>
