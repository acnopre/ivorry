<x-filament-panels::page.simple>
    <div class="space-y-6">
        <div class="text-center">
            <p class="text-gray-600 dark:text-gray-300 text-sm">
                Enter your credentials below
            </p>
        </div>

        @if (session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        <div>
            {{ $this->form }}
        </div>

        <x-filament::button wire:click="submit" class="w-full" type="submit">
            Login
        </x-filament::button>

        <div class="text-center">
            <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition">
                ← Back to Main Login
            </a>
        </div>
    </div>
</x-filament-panels::page.simple>
