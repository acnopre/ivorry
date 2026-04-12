@php
    $member = $getRecord();
    $account = $member->account;
    $isShared = strtoupper($account->plan_type ?? '') === 'SHARED';

    if ($isShared && $member->card_number) {
        $services = \App\Models\MemberService::where('card_number', $member->card_number)
            ->where('account_id', $account->id)
            ->with('service')
            ->get()
            ->filter(fn($ms) => $ms->is_unlimited || $ms->quantity > 0);
    } else {
        $services = $account->services->filter(fn($s) => $s->pivot->is_unlimited || $s->pivot->quantity > 0);
    }
@endphp

@if($services->isEmpty())
    <p class="text-sm text-gray-500">No services assigned</p>
@else
    @if($isShared)
        <div class="mb-2">
            <span class="inline-flex items-center rounded-md bg-warning-50 dark:bg-warning-400/10 px-2 py-1 text-xs font-medium text-warning-600 dark:text-warning-400 ring-1 ring-inset ring-warning-500/20">
                Family ({{ $member->card_number }})
            </span>
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default Quantity</th>
                    @unless($isShared)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</th>
                    @endunless
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @if($isShared)
                    @foreach($services as $ms)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $ms->service->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $ms->is_unlimited ? 'Unlimited' : $ms->quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $ms->is_unlimited ? '—' : $ms->default_quantity }}
                            </td>
                        </tr>
                    @endforeach
                @else
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
                @endif
            </tbody>
        </table>
    </div>
@endif
