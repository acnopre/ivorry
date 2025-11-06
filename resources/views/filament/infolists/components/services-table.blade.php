<div class="fi-in-repeatable-entry-services-table">
    @php
    $services = $getRecord()->services;
    @endphp

    @if ($services->isEmpty())
    {{-- Empty State (As previously refined) --}}
    <div class="p-8 text-center text-gray-400 italic bg-white dark:bg-gray-900 rounded-xl shadow-inner border border-dashed border-gray-300 dark:border-gray-800">
        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
        <p class="font-semibold text-lg text-gray-700 dark:text-gray-300">No Active Services</p>
        <p class="text-sm text-gray-500 dark:text-gray-500">Please check the account configuration for available services.</p>
    </div>
    @else
    {{-- Responsive Container: Hides the traditional table on small screens --}}
    <div class="hidden md:block overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl">
        {{-- DESKTOP/TABLET TABLE VIEW (Your previous structure with sticky column) --}}
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            {{-- Table Header --}}
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="sticky left-0 bg-gray-100 dark:bg-gray-800 px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider shadow-sm">Service</th>
                    <th class="px-3 py-3 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Type</th>
                    <th class="px-3 py-3 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Limit</th>
                    <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Unlimited</th>
                </tr>
            </thead>
            {{-- Table Body --}}
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($services as $service)
                <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/70 hover:bg-primary-50/50 dark:hover:bg-gray-800 transition duration-150">
                    {{-- Sticky Cell --}}
                    <td class="sticky left-0 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/70 px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap shadow-sm">
                        <div class="font-semibold">{{ $service->name }}</div>
                        @if ($service->pivot->remarks)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 max-w-sm truncate">{{ $service->pivot->remarks }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-4 text-sm text-right font-extrabold text-primary-600 dark:text-primary-400 whitespace-nowrap">
                        {{$service->type }}
                    </td>
                    {{-- Data Cells --}}
                    <td class="px-3 py-4 text-sm text-right font-extrabold text-primary-600 dark:text-primary-400 whitespace-nowrap">
                        @if($service->pivot->quantity !=0)
                        {{ number_format($service->pivot->quantity) }}
                        @endif
                    </td>
                    <td class="px-3 py-4 text-center">
                        @if ($service->pivot->is_unlimited)
                        <x-filament::icon icon="heroicon-s-check-circle" class="w-6 h-6 mx-auto" style="color: rgb(var(--success-500));" title="Unlimited" />

                        @else
                        <x-filament::icon icon="heroicon-s-x-circle" class="w-6 h-6 mx-auto" style="color: rgb(var(--danger-500));" title="Limited" />

                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- MOBILE CARD LIST VIEW --}}
    <div class="md:hidden space-y-3">
        @foreach ($services as $service)
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                {{-- Service Name (Primary Focus) --}}
                <h4 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $service->name }}</h4>

                {{-- Unlimited Status Badge --}}
                @if ($service->pivot->is_unlimited)
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-success-500/10 text-success-500 dark:bg-success-400/10">Unlimited</span>
                @else
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-danger-500/10 text-danger-500 dark:bg-danger-400/10">Limited</span>
                @endif
            </div>

            {{-- Service Remarks (if available) --}}
            @if ($service->pivot->remarks)
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $service->pivot->remarks }}</p>
            @endif

            {{-- Data Grid (Limit and Default) --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Limit --}}
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Limit</p>
                    <p class="text-lg font-extrabold text-primary-600 dark:text-primary-400">
                        {{ number_format($service->pivot->quantity) }}
                    </p>
                </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
