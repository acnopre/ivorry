@if($new_fee)
    <div class="flex flex-col gap-1">
        <x-filament::badge :color="$approved_at ? 'success' : 'warning'" size="sm">
            {{ $approved_at ? 'Approved' : 'Pending' }} — &#8369;{{ number_format($new_fee, 2) }}
        </x-filament::badge>
        @if($effective_date)
            <span class="text-xs text-gray-400 dark:text-gray-500 pl-1">
                Effective {{ \Carbon\Carbon::parse($effective_date)->format('M d, Y') }}
            </span>
        @endif
    </div>
@else
    <span class="text-xs text-gray-400">—</span>
@endif
