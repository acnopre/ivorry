<x-filament-panels::page>
    @php
    $clinic = $this->getClinic();
    @endphp

    @if($clinic)
    <x-filament::section>
        <x-slot name="heading">Clinic Information</x-slot>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Clinic Name</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->clinic_name }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Registered Name</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->registered_name ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">PRC License No</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->prc_license_no ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">PRC Expiration</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->prc_expiration_date?->format('M d, Y') ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">PTR No</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->ptr_no ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">TIN</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->tax_identification_no ?? 'N/A' }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Contact Information</x-slot>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Address</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->complete_address ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Landline</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->clinic_landline ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Mobile</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->clinic_mobile ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Email</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->clinic_email ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Viber</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->viber_no ?? 'N/A' }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Bank Information</x-slot>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Account Name</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->bank_account_name ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Account Number</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->bank_account_number ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Bank Name</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->bank_name ?? 'N/A' }}</p>
            </div>
            <div class="space-y-1">
                <p class="font-semibold text-gray-700 dark:text-gray-300">Bank Branch</p>
                <p class="text-gray-900 dark:text-white">{{ $clinic->bank_branch ?? 'N/A' }}</p>
            </div>
        </div>
    </x-filament::section>
    @else
    <x-filament::section>
        <p class="text-gray-600">No clinic profile found.</p>
    </x-filament::section>
    @endif
</x-filament-panels::page>
