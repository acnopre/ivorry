<div class="space-y-6">
    @php
    // Group the passed $records by period
    $grouped = collect($records)->groupBy('period');
    @endphp

    @forelse ($grouped as $period => $items)
    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-lg">
        {{-- Enhanced Group Header --}}
        <div class="bg-primary-50 dark:bg-gray-800/50 px-6 py-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
            <div class="font-bold text-lg text-primary-600 dark:text-primary-400 flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6 text-primary-500" />
                <span class="text-xl tracking-tight">{{ $period }}</span>
                {{-- Service count badge --}}
                <span class="text-sm font-medium bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300 rounded-full px-3 py-1 ml-4">
                    {{ count($items) }} Services
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Enhanced Table Header --}}
                <thead class="bg-gray-100 dark:bg-gray-900/50 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Remarks</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Action Date</th>
                    </tr>
                </thead>
                {{-- Enhanced Table Body with Zebra Striping --}}
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($items as $item)
                    <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/70 hover:bg-primary-50 dark:hover:bg-primary-950 transition duration-150">
                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['service_name'] }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold text-gray-700 dark:text-gray-300">{{ $item['quantity'] }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item['remarks'] }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $item['created_at'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    {{-- Enhanced Empty State --}}
    <div class="p-8 text-center bg-white dark:bg-gray-800 rounded-xl shadow-lg">
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-600" />
        <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">
            No **renewal history** available.
        </p>
    </div>
    @endforelse
</div>
