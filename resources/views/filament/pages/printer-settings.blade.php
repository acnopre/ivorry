<x-filament-panels::page>
    <div class="space-y-6">
        @if(empty($printers))
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
            <x-heroicon-o-printer class="w-16 h-16 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Printers Found</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">No printers are currently configured on this system.</p>
        </div>
        @else
        <div class="grid gap-4">
            @foreach ($printers as $printer)
            @php
            $isReady = $printer['connected'] && str_contains($printer['status'], 'idle');
            $isOffline = !$printer['connected'] || str_contains($printer['status'], 'disabled');
            @endphp

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="border-left: 4px solid {{ $isReady ? '#22c55e' : ($isOffline ? '#ef4444' : '#eab308') }};">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-full" style="background-color: {{ $isReady ? '#dcfce7' : ($isOffline ? '#fee2e2' : '#fef9c3') }};">
                                <x-heroicon-o-printer class="w-6 h-6" style="color: {{ $isReady ? '#16a34a' : ($isOffline ? '#dc2626' : '#ca8a04') }};" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $printer['name'] }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ ucfirst($printer['status']) }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: {{ $isReady ? '#dcfce7' : ($isOffline ? '#fee2e2' : '#fef9c3') }}; color: {{ $isReady ? '#166534' : ($isOffline ? '#991b1b' : '#854d0e') }};">
                            {{ $isReady ? 'Ready' : ($isOffline ? 'Offline' : 'Busy') }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-filament-panels::page>
