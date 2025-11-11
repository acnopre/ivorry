<div class="space-y-6">
    @php
    $services = $getRecord()->services;
    $groupedServices = $services->groupBy('type');
    @endphp

    {{-- EMPTY STATE --}}
    @if ($services->isEmpty())
    <div class="p-10 text-center rounded-2xl border border-dashed border-primary-400 dark:border-primary-700 bg-white dark:bg-gray-900 shadow-md">
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" />
        <p class="mt-4 text-base font-semibold text-gray-800 dark:text-gray-200">No Active Services Found</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Check the account configuration or contact support.</p>
    </div>
    @else
    @foreach ($groupedServices as $type => $servicesOfType)
    <x-filament::section class="rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900">

        {{-- Header --}}
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-md bg-primary-50 dark:bg-primary-900/30">
                        <x-filament::icon icon="heroicon-o-rectangle-stack" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $type === 'basic' ? 'Basic Services' : 'Enhancement Services' }}
                    </h3>
                </div>

                <span class="text-xs font-medium px-3 py-1 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300 border border-primary-200 dark:border-primary-700">
                    {{ count($servicesOfType) }} {{ Str::plural('Item', count($servicesOfType)) }}
                </span>
            </div>
        </x-slot>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-t border-gray-200 dark:border-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/70 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-6 py-3">Service</th>
                        <th class="text-right px-6 py-3">Quantity</th>
                        <th class="text-center px-6 py-3">Unlimited</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($servicesOfType as $service)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $service->name }}
                            </div>
                            @if ($service->pivot->remarks)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Remarks: {{ $service->pivot->remarks }}
                            </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right font-mono">
                            @if ($service->pivot->is_unlimited)
                            <span class="text-gray-400 italic">N/A</span>
                            @elseif ($service->pivot->quantity > 0)
                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($service->pivot->quantity) }}
                            </span>
                            @else
                            <span class="text-lg font-bold text-danger-600 dark:text-danger-400">0</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if ($service->pivot->is_unlimited)
                            <x-filament::icon icon="heroicon-s-check-circle" class="w-5 h-5 mx-auto text-success-600 dark:text-success-500" title="Unlimited" />
                            @else
                            <x-filament::icon icon="heroicon-s-x-circle" class="w-5 h-5 mx-auto text-gray-300 dark:text-gray-700" title="Limited" />
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @endforeach
    @endif
</div>
