<div class="space-y-4 py-2">
    @forelse ($records as $index => $renewal)

    <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm overflow-hidden" x-data="{ open: false }">

        {{-- Header --}}
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                    <x-heroicon-s-arrow-path class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            Renewal #{{ count($records) - $index }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                            {{ count($renewal['records']) }} {{ Str::plural('service', count($renewal['records'])) }}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-calendar-days class="h-3.5 w-3.5" />
                            {{ $renewal['created_at'] }}
                        </span>
                        @if($renewal['requested_by'])
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-user class="h-3.5 w-3.5" />
                            Requested by <strong class="ml-1 text-gray-700 dark:text-gray-300">{{ $renewal['requested_by'] }}</strong>
                        </span>
                        @endif
                        @if($renewal['approved_by'])
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-check-badge class="h-3.5 w-3.5 text-success-500" />
                            Approved by <strong class="ml-1 text-gray-700 dark:text-gray-300">{{ $renewal['approved_by'] }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            <x-heroicon-m-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" ::class="{ 'rotate-180': open }" />
        </button>

        {{-- Body --}}
        <div x-show="open" x-collapse>
            <div class="border-t border-gray-100 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">

                {{-- Coverage Period --}}
                <div class="px-6 py-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-heroicon-m-calendar-days class="h-3.5 w-3.5" />
                        Coverage Period
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg px-4 py-3 {{ $renewal['old_effective'] ? 'bg-gray-50 dark:bg-white/5' : 'bg-gray-50 dark:bg-white/5' }}">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5">Effective Date</p>
                            @if($renewal['old_effective'] && $renewal['old_effective'] !== $renewal['new_effective'])
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400">Before</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 line-through">{{ $renewal['old_effective'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">After</span>
                                    <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $renewal['new_effective'] ?? '—' }}</span>
                                </div>
                            </div>
                            @else
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $renewal['new_effective'] ?? '—' }}</p>
                            @endif
                        </div>
                        <div class="rounded-lg px-4 py-3 bg-gray-50 dark:bg-white/5">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5">Valid Until</p>
                            @if($renewal['old_expiration'] && $renewal['old_expiration'] !== $renewal['new_expiration'])
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400">Before</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 line-through">{{ $renewal['old_expiration'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">After</span>
                                    <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $renewal['new_expiration'] ?? '—' }}</span>
                                </div>
                            </div>
                            @else
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $renewal['new_expiration'] ?? '—' }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Services --}}
                @if(count($renewal['records']) > 0)
                <div class="px-6 py-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-heroicon-m-rectangle-stack class="h-3.5 w-3.5" />
                        Services
                    </p>
                    <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Service</th>
                                    <th class="px-4 py-3 text-center font-semibold">Quantity</th>
                                    <th class="px-4 py-3 text-left font-semibold">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-gray-900">
                                @foreach ($renewal['records'] as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['service_name'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['is_unlimited'] ?? false)
                                        <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 font-semibold text-success-700 dark:bg-success-900/30 dark:text-success-400">Unlimited</span>
                                        @else
                                        <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $item['quantity'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 italic">{{ $item['remarks'] ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>
    @empty
    <div class="flex flex-col items-center justify-center rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 py-16 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
            <x-heroicon-o-arrow-path class="h-7 w-7 text-gray-400 dark:text-gray-500" />
        </div>
        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No renewal history</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This account has no renewal history yet.</p>
    </div>
    @endforelse
</div>
