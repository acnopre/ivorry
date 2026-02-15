<div class="space-y-4">
    @php
    $grouped = collect($records)->groupBy('period');
    @endphp

    @forelse ($grouped as $period => $items)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
        {{-- HEADER --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                        <x-heroicon-o-calendar-days class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $period }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($items) }} {{ Str::plural('service', count($items)) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left px-6 py-3 font-semibold text-gray-700 dark:text-gray-300">Service</th>
                        <th class="text-center px-6 py-3 font-semibold text-gray-700 dark:text-gray-300">Quantity</th>
                        <th class="text-left px-6 py-3 font-semibold text-gray-700 dark:text-gray-300">Remarks</th>
                        <th class="text-right px-6 py-3 font-semibold text-gray-700 dark:text-gray-300">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $item['service_name'] }}</td>
                        <td class="px-6 py-4 text-center font-mono text-primary-600 dark:text-primary-400">{{ $item['quantity'] ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-xs italic">{{ $item['remarks'] ?? '—' }}</td>
                        <td class="px-6 py-4 text-right text-xs text-gray-500 dark:text-gray-400">{{ $item['created_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <x-heroicon-o-archive-box-x-mark class="w-8 h-8 mx-auto mb-3 text-gray-400" />
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No renewal history found</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Logs will appear here when available.</p>
    </div>
    @endforelse
</div>
