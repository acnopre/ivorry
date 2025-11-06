<div class="space-y-6">
    @php
    // Group records by period
    $grouped = collect($records)->groupBy('period');
    @endphp

    @forelse ($grouped as $period => $items)
    <section class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-lg dark:shadow-xl">
        {{-- 1. ENHANCED GROUP HEADER --}}
        <header class="flex items-center justify-between px-4 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-md bg-primary-100 dark:bg-primary-900/30">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>

                <div>
                    {{-- Increased font size and weight for the period --}}
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $period }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Renewal history (Grouped)</p>
                </div>
            </div>

            {{-- Slightly larger, more prominent badge --}}
            <div class="text-sm font-semibold px-4 py-1 rounded-full bg-primary-600 text-white">
                {{ count($items) }} Items
            </div>
        </header>

        <div class="w-full overflow-auto">
            <table class="w-full text-sm">
                {{-- 2. TABLE HEADER IMPROVED SPACING & ALIGNMENT --}}
                <thead class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-xs text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-4 py-3 border-r border-gray-200 dark:border-gray-800">Service</th>
                        {{-- Consistent right alignment for Qty and Date --}}
                        <th class="text-right px-4 py-3 border-r border-gray-200 dark:border-gray-800">Qty</th>
                        <th class="text-right px-4 py-3">Action Date</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($items as $item)
                    {{-- More distinct hover color --}}
                    <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800 transition-colors">
                        {{-- Better vertical padding --}}
                        <td class="px-4 py-4 whitespace-nowrap border-r border-gray-200 dark:border-gray-800">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $item['service_name'] }}</div>
                            @if (!empty($item['remarks']))
                            {{-- Increased contrast/visibility for remarks --}}
                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1 truncate max-w-lg">{{ $item['remarks'] }}</div>
                            @endif
                        </td>

                        {{-- Better vertical padding and stronger font weight --}}
                        <td class="px-4 py-4 text-right font-bold border-r border-gray-200 dark:border-gray-800">{{ $item['quantity'] }}</td>

                        {{-- Consistent right alignment and better vertical padding --}}
                        <td class="px-4 py-4 text-right text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($item['created_at'])->isoFormat('MMM D, YYYY [at] h:mm A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @empty
    {{-- 3. EMPTY STATE REFINEMENT --}}
    <div class="p-8 text-center rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
        {{-- Slightly softer color for the icon in the empty state --}}
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-700" />
        <p class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">No renewal history found</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Logs will appear here when available.</p>
    </div>
    @endforelse
</div>
