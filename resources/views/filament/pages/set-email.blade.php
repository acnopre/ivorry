<x-filament-panels::page.simple>
    @if($this->emailSaved)
        <div class="flex flex-col items-center gap-6 py-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-100 dark:bg-success-400/20">
                <x-heroicon-o-check-circle class="h-10 w-10 text-success-600 dark:text-success-400" />
            </div>

            <div class="text-center">
                <p class="text-lg font-semibold text-gray-900 dark:text-white">Email Set Successfully!</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your account email has been updated.</p>
            </div>

            <div class="w-full rounded-xl bg-gray-50 dark:bg-white/5 ring-1 ring-gray-200 dark:ring-white/10 px-4 py-3 text-center">
                <p class="text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Email Address</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()?->email }}</p>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                A password has been sent to your email. Please check your inbox.
            </p>
        </div>
    @else
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">Please provide a valid email to continue using the system.</p>

        {{ $this->form }}

        <x-filament::button wire:click="save" class="w-full">
            Update Email
        </x-filament::button>
    @endif
</x-filament-panels::page.simple>
