@php
    $services = $getRecord()->account->services->filter(fn($service) => $service->pivot->is_unlimited || $service->pivot->quantity > 0);
@endphp

@if($services->isEmpty())
    <p class="text-sm text-gray-500">No services assigned</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($services as $service)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $service->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $service->pivot->is_unlimited ? 'Unlimited' : $service->pivot->quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $service->pivot->is_unlimited ? '—' : $service->pivot->default_quantity }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $service->pivot->remarks ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
