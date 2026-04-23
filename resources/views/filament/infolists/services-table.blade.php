@php
    $member = $getRecord();
    $account = $member->account;
    $isShared = strtoupper($account->plan_type ?? '') === 'SHARED';

    \App\Models\MemberService::initializeForCard($member->card_number, $account->id);

    $services = \App\Models\MemberService::where('card_number', $member->card_number)
        ->where('account_id', $account->id)
        ->with('service')
        ->get();
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty (Current/Default)</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($services as $ms)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $ms->service->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold">
                            @if($ms->is_unlimited)
                                <x-filament::badge color="success" size="sm">Unlimited</x-filament::badge>
                            @elseif($ms->quantity == 0)
                                <span class="text-danger-600 dark:text-danger-400">{{ $ms->quantity }}/{{ $ms->default_quantity }}</span>
                            @else
                                {{ $ms->quantity }}/{{ $ms->default_quantity }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
