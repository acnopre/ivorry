<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Print Mode --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-gray-400" />
                    Print Mode
                </div>
            </x-slot>

            <div class="flex items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div @class([
                        'p-3 rounded-xl',
                        'bg-warning-50 dark:bg-warning-500/10' => $simulatePrint,
                        'bg-gray-100 dark:bg-gray-800' => !$simulatePrint,
                    ])>
                        @if($simulatePrint)
                            <x-heroicon-o-beaker class="w-6 h-6 text-warning-500" />
                        @else
                            <x-heroicon-o-printer class="w-6 h-6 text-gray-400" />
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $simulatePrint ? 'Simulation Mode' : 'Live Printing' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $simulatePrint
                                ? 'Claims are marked as Processed immediately — no printer required.'
                                : 'Claims are sent to the configured printer before being marked as Processed.' }}
                        </p>
                    </div>
                </div>

                <x-filament::button
                    wire:click="toggleSimulate"
                    :color="$simulatePrint ? 'warning' : 'gray'"
                    :icon="$simulatePrint ? 'heroicon-o-x-circle' : 'heroicon-o-beaker'"
                    size="sm"
                >
                    {{ $simulatePrint ? 'Disable Simulation' : 'Enable Simulation' }}
                </x-filament::button>
            </div>

            @if($simulatePrint)
                <x-filament::section class="mt-4 !bg-warning-50 dark:!bg-warning-500/10 !ring-warning-200 dark:!ring-warning-500/20">
                    <div class="flex items-start gap-3 text-sm text-warning-700 dark:text-warning-400">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 mt-0.5" />
                        <span>
                            <strong>Simulation is active.</strong>
                            Printing actions will skip the printer entirely and immediately mark claims as <strong>Processed</strong>.
                            Disable this before going live.
                        </span>
                    </div>
                </x-filament::section>
            @endif
        </x-filament::section>

        {{-- Printers --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-printer class="w-5 h-5 text-gray-400" />
                    Detected Printers
                </div>
            </x-slot>

            @if(empty($printers))
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="p-4 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                        <x-heroicon-o-printer class="w-8 h-8 text-gray-400" />
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">No Printers Found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No printers are currently configured on this system.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($printers as $printer)
                    @php
                        $isReady   = $printer['connected'] && str_contains($printer['status'], 'idle');
                        $isOffline = !$printer['connected'] || str_contains($printer['status'], 'disabled');
                        $color     = $isReady ? 'success' : ($isOffline ? 'danger' : 'warning');
                        $label     = $isReady ? 'Ready' : ($isOffline ? 'Offline' : 'Busy');
                    @endphp
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 dark:bg-gray-800/50 px-4 py-3 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-printer @class([
                                'w-5 h-5',
                                'text-success-500' => $isReady,
                                'text-danger-500'  => $isOffline,
                                'text-warning-500' => !$isReady && !$isOffline,
                            ]) />
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $printer['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($printer['status']) }}</p>
                            </div>
                        </div>
                        <x-filament::badge :color="$color">{{ $label }}</x-filament::badge>
                    </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Debug --}}
        <x-filament::section :collapsible="true" :collapsed="true">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-command-line class="w-5 h-5 text-gray-400" />
                    Debug Output
                </div>
            </x-slot>

            <div class="space-y-3 text-xs font-mono">
                @foreach([
                    'whoami'      => $debugOutput['whoami'],
                    'which lp'    => $debugOutput['which_lp'],
                    'which lpstat'=> $debugOutput['which_lpstat'],
                    'lpstat -d'   => implode(' ', $debugOutput['lpstat_d']) ?: '—',
                ] as $label => $value)
                <div class="flex gap-3">
                    <span class="text-gray-400 dark:text-gray-500 w-28 shrink-0">{{ $label }}</span>
                    <span class="text-gray-700 dark:text-gray-300">{{ $value ?: '—' }}</span>
                </div>
                @endforeach

                @foreach(['lpstat -p' => $debugOutput['lpstat_p'], 'lpstat -a' => $debugOutput['lpstat_a']] as $label => $lines)
                <div>
                    <span class="text-gray-400 dark:text-gray-500">{{ $label }}</span>
                    @if(empty($lines))
                        <span class="ml-3 text-danger-500">No output</span>
                    @else
                        <pre class="mt-1 rounded-lg bg-gray-100 dark:bg-gray-800 p-3 text-gray-700 dark:text-gray-300 overflow-x-auto">{{ implode("\n", $lines) }}</pre>
                    @endif
                </div>
                @endforeach
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
