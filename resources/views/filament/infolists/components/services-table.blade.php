<div class="fi-in-repeatable-entry-services-table">
    @php
        $services = $getRecord()->services;
    @endphp

    @if ($services->isEmpty())
        <div class="p-4 text-center text-gray-500 italic bg-gray-50 dark:bg-gray-800 rounded-lg">
            No active services found for this account.
        </div>
    @else
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Table Header --}}
                <thead class="bg-gray-100 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Service Name</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Default Qty</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Unlimited</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($services as $service)
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/70 hover:bg-primary-50 dark:hover:bg-primary-950 transition duration-150">
                            {{-- Service Name --}}
                            <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $service->name }}
                            </td>
                            {{-- Quantity (Right Aligned) --}}
                            <td class="px-6 py-3 text-sm text-right font-semibold text-primary-600 dark:text-primary-400">
                                {{ number_format($service->pivot->quantity) }}
                            </td>
                            {{-- Default Quantity (Right Aligned, lighter) --}}
                            <td class="px-6 py-3 text-sm text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($service->pivot->default_quantity) }}
                            </td>
                            {{-- Unlimited (Centered Badge) --}}
                            <td class="px-6 py-3 text-center">
                                @if ($service->pivot->is_unlimited)
                                    <span class="inline-flex items-center rounded-md bg-success-50/50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                        YES
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-danger-50/50 px-2 py-1 text-xs font-medium text-danger-700 ring-1 ring-inset ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                        NO
                                    </span>
                                @endif
                            </td>
                            {{-- Remarks --}}
                            <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $service->pivot->remarks }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>