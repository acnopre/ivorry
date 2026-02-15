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

    <!-- Printing Modal -->
    <div x-data="{ show: false, status: 'Preparing document...', progress: 0 }" 
         @start-printing.window="show = true; status = 'Preparing document...'; progress = 0"
         @update-progress.window="status = $event.detail.status; progress = $event.detail.progress"
         @close-printing.window="show = false; status = 'Preparing document...'; progress = 0"
         x-show="show" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-xl">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 mx-auto text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Printing ADC</h3>
                <p class="text-sm text-gray-600 mb-4" x-text="status"></p>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2" x-text="`${progress}% complete`"></p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
