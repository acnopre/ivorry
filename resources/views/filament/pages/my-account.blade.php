<x-filament::page>
    {{-- Account Information Section (Kept the same for focus) --}}
    @if ($this->hasAccount())
    <x-filament::section>
        <x-slot name="heading">
            Account Information
        </x-slot>

        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-4 lg:gap-6 text-sm">
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Company Name</p>
                <p class="text-gray-900 dark:text-white">{{ $account->company_name }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Policy Code</p>
                <p class="text-gray-900 dark:text-white">{{ $account->policy_code }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Effective Date</p>
                <p class="text-gray-900 dark:text-white">{{ $account->effective_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Expiration Date</p>
                <p class="text-gray-900 dark:text-white">{{ $account->expiration_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            @php
            $status = $account->account_status;

            $color = match ($status) {
            'active' => 'success',
            'inactive' => 'warning',
            'expired' => 'danger',
            default => 'gray',
            };

            $label = ucfirst($status);
            @endphp
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Status</p>
                <x-filament::badge color="{{ $color }}" size="md" class="inline-flex">
                    {{ $label }}
                </x-filament::badge>
            </div>

    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Services Covered
        </x-slot>

        @if ($account->services->isNotEmpty())
        <div class="overflow-x-auto">
            {{-- Added 'table-auto' to ensure columns adjust based on content and available space --}}
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm table-auto">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">
                            Service Name
                        </th>
                        {{-- Removed w-24 --}}
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">
                            Quantity
                        </th>
                        {{-- Removed w-28 --}}
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">
                            Unlimited
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">
                            Remarks
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($account->services as $service)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $service->name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ $service->pivot->quantity ?? '—' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $service->pivot->is_unlimited ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $service->pivot->remarks ?? 'No specific remarks.' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-sm">No services are currently assigned to this account.</p>
        @endif
    </x-filament::section>

    {{-- Fallback Case (Kept the same for focus) --}}
    @else
    <x-filament::section>
        <p class="text-gray-600 text-sm">
            No account is assigned to your user profile.
        </p>
    </x-filament::section>
    @endif
</x-filament::page>
