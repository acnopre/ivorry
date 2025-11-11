<div class="space-y-6">
    @php
    $grouped = collect($records)->groupBy('period');
    @endphp

    @forelse ($grouped as $period => $items)
    {{-- GROUP CONTAINER --}}
    <x-filament::section class="rounded-2xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900">

        {{-- HEADER --}}
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-md bg-primary-50 dark:bg-primary-900/30">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $period }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Renewal history (Grouped)
                        </p>
                    </div>
                </div>

                <span class="text-xs font-medium px-3 py-1 rounded-full bg-primary-600 text-white shadow-sm">
                    {{ count($items) }} {{ Str::plural('Item', count($items)) }}
                </span>
            </div>
        </x-slot>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-t border-gray-200 dark:border-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/70 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-3 border-r border-gray-200 dark:border-gray-800">
                            Service
                        </th>
                        <th class="text-right px-4 py-3 border-r border-gray-200 dark:border-gray-800">
                            Qty
                        </th>
                        <th class="text-right px-4 py-3">
                            Action Date
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        {{-- Service --}}
                        <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-800">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $item['service_name'] }}
                            </div>

                            @if (!empty($item['remarks']))
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate max-w-lg">
                                Remarks: {{ $item['remarks'] }}
                            </div>
                            @endif
                        </td>

                        {{-- Quantity --}}
                        <td class="px-4 py-4 text-right font-semibold border-r border-gray-200 dark:border-gray-800">
                            @if ($item['quantity'] > 0)
                            <span class="text-primary-600 dark:text-primary-400">{{ $item['quantity'] }}</span>
                            @else
                            <span class="text-danger-600 dark:text-danger-400">0</span>
                            @endif
                        </td>

                        {{-- Action Date --}}
                        <td class="px-4 py-4 text-right text-xs text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($item['created_at'])->isoFormat('MMM D, YYYY [at] h:mm A') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @empty
    {{-- EMPTY STATE --}}
    <div class="p-10 text-center rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-700" />
        <p class="mt-4 text-base font-semibold text-gray-800 dark:text-gray-200">
            No renewal history found
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Logs will appear here when available.
        </p>
    </div>
    @endforelse
</div>
