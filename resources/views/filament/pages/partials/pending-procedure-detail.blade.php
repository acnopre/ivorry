@php
    $member = $record->member;
    $clinic = $record->clinic;
    $service = $record->service;
    $units = $record->units ?? collect();
    $account = $member?->account;
@endphp

<div class="space-y-4 text-sm">
    {{-- Procedure Info --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Procedure Information</h4>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Approval Code</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->approval_code ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Service</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $service?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Applied Fee</p>
                <p class="font-medium text-gray-900 dark:text-white">₱{{ number_format($record->applied_fee, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Availment Date</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->availment_date?->format('M d, Y') ?? '—' }}</p>
            </div>
            @if($units->count())
                <div class="col-span-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Units</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($units as $unit)
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300">
                                {{ $unit->unitType?->name ?? '—' }}: {{ $unit->name ?? '—' }}
                                @if($unit->pivot->quantity > 1)
                                    (x{{ $unit->pivot->quantity }})
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($record->remarks)
                <div class="col-span-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Remarks</p>
                    <p class="text-gray-900 dark:text-white">{{ $record->remarks }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Member Info --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Member Information</h4>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Name</p>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? '')) ?: '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Account</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $account?->company_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Card Number</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $member?->card_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Plan Type</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $account?->plan_type ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Clinic Info --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Clinic Information</h4>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Clinic</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $clinic?->clinic_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Dentist</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->dentist_name }}</p>
            </div>
        </div>
    </div>
</div>
