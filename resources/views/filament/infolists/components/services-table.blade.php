<div class="fi-in-repeatable-entry-services-table space-y-6">
    @php
    $services = $getRecord()->services;
    $groupedServices = $services->groupBy('type');
    @endphp

    @if ($services->isEmpty())
    {{-- EMPTY STATE --}}
    <div class="p-8 text-center rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-700" />
        <p class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">No Active Services Found</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Check the account configuration or contact support.</p>
    </div>
    @else
    @foreach ($groupedServices as $type => $servicesOfType)
    {{-- GROUP HEADER --}}
    <header class="flex items-center justify-between px-4 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 rounded-t-xl">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-primary-100 dark:bg-primary-900/30">
                <x-filament::icon icon="heroicon-o-rectangle-stack" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $type == 'basic' ? 'Basic Services' : 'Enhancement Services' }}</h3>
                {{-- <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Service group summary</p> --}}
            </div>
        </div>

        {{-- Group Count Badge --}}
        <div class="text-sm font-semibold px-4 py-1 rounded-full bg-primary-600 text-white">
            {{ count($servicesOfType) }} Services
        </div>
    </header>

    {{-- TABLE SECTION --}}
    <div class="w-full overflow-auto">
        <table class="w-full text-sm">
            {{-- Table Header --}}
            <thead class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="text-left px-4 py-3 border-r border-gray-200 dark:border-gray-800">Service</th>
                    <th class="text-right px-4 py-3 border-r border-gray-200 dark:border-gray-800">Quantity</th>
                    <th class="text-center px-4 py-3">Unlimited</th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($servicesOfType as $service)
                <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800 transition-colors">
                    {{-- SERVICE NAME + REMARKS --}}
                    <td class="px-4 py-4 whitespace-nowrap border-r border-gray-200 dark:border-gray-800">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $service->name }}</div>
                        @if ($service->pivot->remarks)
                        <div class="text-xs text-gray-600 dark:text-gray-300 mt-1 truncate max-w-lg">
                            {{ $service->pivot->remarks }}
                        </div>
                        @endif
                    </td>

                    {{-- QUANTITY DISPLAY --}}
                    <td class="px-4 py-4 text-right font-bold border-r border-gray-200 dark:border-gray-800">
                        @if ($service->pivot->is_unlimited)
                        <span class="text-gray-400 dark:text-gray-600 italic">-</span>
                        @elseif ($service->pivot->quantity != 0)
                        <span class="text-primary-600 dark:text-primary-400">
                            {{ number_format($service->pivot->quantity) }}
                        </span>
                        @else
                        <span class="text-danger-600 dark:text-danger-400">0</span>
                        @endif
                    </td>

                    {{-- UNLIMITED ICON --}}
                    <td class="px-4 py-4 text-center">
                        @if ($service->pivot->is_unlimited)
                        <x-filament::icon icon="heroicon-s-check-circle" class="w-6 h-6 mx-auto" style="color: rgb(var(--success-500));" title="Unlimited" />
                        @else
                        <x-filament::icon icon="heroicon-s-x-circle" class="w-6 h-6 mx-auto text-danger-600 dark:text-danger-500" title="Limited" />
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
</div>
