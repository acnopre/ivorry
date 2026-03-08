<x-filament::page>
    {{-- Account Information Section (Kept the same for focus) --}}
    @if ($this->hasAccount())
    <x-filament::section heading="Account Information">
        <x-filament::grid :default="1" :sm="2" :lg="2" class="text-sm gap-6">
            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Company Name</p>
                <p class="text-gray-900 dark:text-white">{{ $account->company_name }}</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Policy Code</p>
                <p class="text-gray-900 dark:text-white">{{ $account->policy_code }}</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">HIP</p>
                <p class="text-gray-900 dark:text-white">{{ $account->hip ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Card Used</p>
                <p class="text-gray-900 dark:text-white">{{ $account->card_used ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Effective Date</p>
                <p class="text-gray-900 dark:text-white">
                    {{ $account->effective_date?->format('M d, Y') ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Expiration Date</p>
                <p class="text-gray-900 dark:text-white">
                    {{ $account->expiration_date?->format('M d, Y') ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Plan Type</p>
                <p class="text-gray-900 dark:text-white">{{ $account->plan_type ?? 'N/A' }}</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Coverage Type</p>
                <p class="text-gray-900 dark:text-white">
                    {{ $account->coverage_period_type ?? 'N/A' }}
                </p>
            </div>

            @php
            $status = $account->account_status;
            $color = match ($status) {
            'active' => 'success',
            'inactive' => 'warning',
            'expired' => 'danger',
            default => 'gray',
            };
            @endphp

            <div>
                <p class="font-semibold text-gray-700 dark:text-gray-300">Status</p>
                <x-filament::badge color="{{ $color }}" size="md" class="inline-flex">

                    {{ ucfirst($status) }}
                </x-filament::badge>
            </div>
        </x-filament::grid>
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
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Unit Type</th>

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
                    @if($service->pivot->quantity > 0 || $service->pivot->is_unlimited)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $service->name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ $service->type }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ $service->unit_type }}
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
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-sm">No services are currently assigned to this account.</p>
        @endif
    </x-filament::section>

    {{-- Procedures Section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Procedures
        </x-slot>
        @php
        $procedures = $account->procedures->where('member_id', auth()->user()->member?->id ?? 0);
        @endphp

        @if ($procedures->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm table-auto">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Member Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Procedure Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Clinic</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Availment Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Dentist</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($procedures as $procedure)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $procedure->member->first_name }} {{ $procedure->member->last_name }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $procedure->service->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $procedure->clinic->clinic_name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $procedure->availment_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $procedure->quantity ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $procedure->dentist_name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ ucfirst($procedure->status) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-sm">No procedures are currently recorded for this account.</p>
        @endif
    </x-filament::section>
    @else
    <x-filament::section>
        <div class="text-center py-8">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-12 h-12 mx-auto text-warning-500 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Account Found</h3>
            <p class="text-gray-600 dark:text-gray-400">No account is assigned to your user profile. Please contact support for assistance.</p>
        </div>
    </x-filament::section>
    @endif
</x-filament::page>
