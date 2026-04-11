<x-filament-panels::page>
    @php
        $clinic = $this->getClinic();
        $ownerDentist = $clinic?->dentists?->firstWhere('is_owner', true);
        $associateDentists = $clinic?->dentists?->where('is_owner', false) ?? collect();
        $basicServices = $clinic?->services?->filter(fn($s) => $s->type === 'basic') ?? collect();
        $enhancementServices = $clinic?->services?->filter(fn($s) => $s->type === 'enhancement') ?? collect();
        $specialServices = $clinic?->services?->filter(fn($s) => $s->type === 'special') ?? collect();

        $region = $clinic?->region_id ? \App\Models\Region::find($clinic->region_id)?->name : null;
        $province = $clinic?->province_id ? \App\Models\Province::find($clinic->province_id)?->name : null;
        $municipality = $clinic?->municipality_id ? \App\Models\Municipality::find($clinic->municipality_id)?->name : null;
        $barangay = $clinic?->barangay_id ? \App\Models\Barangay::find($clinic->barangay_id)?->name : null;
    @endphp

    @if($clinic)
        {{-- Clinic Information --}}
        <x-filament::section icon="heroicon-o-building-office">
            <x-slot name="heading">Clinic Information</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Clinic Name</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $clinic->clinic_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registered Name</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->registered_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Accreditation Status</p>
                    <p class="mt-1 flex">
                        <x-filament::badge :color="match($clinic->accreditation_status) {
                            'ACTIVE' => 'success',
                            'INACTIVE' => 'danger',
                            'SILENT' => 'warning',
                            default => 'info',
                        }">
                            {{ $clinic->accreditation_status ?? '—' }}
                        </x-filament::badge>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fee Approval Status</p>
                    <p class="mt-1 flex">
                        <x-filament::badge :color="match(strtoupper($clinic->fee_approval ?? '')) {
                            'PENDING' => 'warning',
                            'APPROVED' => 'success',
                            default => 'gray',
                        }">
                            {{ ucfirst($clinic->fee_approval ?? '—') }}
                        </x-filament::badge>
                    </p>
                </div>
                @if($clinic->hip)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">HIP</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->hip->name }}</p>
                    </div>
                @endif
                @if($clinic->is_branch)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Branch</p>
                        <p class="mt-1 text-gray-900 dark:text-white">Yes</p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Location & Contact --}}
        <x-filament::section icon="heroicon-o-map-pin">
            <x-slot name="heading">Location & Contact</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Complete Address</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->complete_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Street</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->street ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barangay</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $barangay ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">City / Municipality</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $municipality ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Province</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $province ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Region</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $region ?? '—' }}</p>
                </div>
                @if($clinic->alt_address)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alternative Address</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->alt_address }}</p>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Landline</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_landline ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mobile</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_mobile ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_email ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Viber</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->viber_no ?? '—' }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Accreditation & Tax --}}
        <x-filament::section icon="heroicon-o-document-text" collapsible>
            <x-slot name="heading">Accreditation & Tax</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PRC License No</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->prc_license_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PRC Expiration</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->prc_expiration_date ? \Carbon\Carbon::parse($clinic->prc_expiration_date)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PTR No</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->ptr_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PTR Date Issued</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->ptr_date_issued ? \Carbon\Carbon::parse($clinic->ptr_date_issued)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">TIN</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->tax_identification_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">VAT Type</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->vat_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Withholding Tax</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->withholding_tax ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Business Type</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->business_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SEC Registration No</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->sec_registration_no ?? '—' }}</p>
                </div>
                @if($clinic->update_info_1903)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">BIR 1903 Update</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->update_info_1903 }}</p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Owner Dentist --}}
        @if($ownerDentist)
            <x-filament::section icon="heroicon-o-user-circle">
                <x-slot name="heading">Clinic Owner</x-slot>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                            Dr. {{ $ownerDentist->first_name }} {{ $ownerDentist->middle_initial ? $ownerDentist->middle_initial . '.' : '' }} {{ $ownerDentist->last_name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PRC License #</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $ownerDentist->prc_license_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PRC Expiry</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $ownerDentist->prc_expiration_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    @if($ownerDentist->specializations && $ownerDentist->specializations->count())
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Specializations</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($ownerDentist->specializations as $spec)
                                    <x-filament::badge color="info">{{ $spec->name }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Associate Dentists --}}
        @if($associateDentists->count())
            <x-filament::section icon="heroicon-o-user-group" collapsible>
                <x-slot name="heading">Associate Dentists ({{ $associateDentists->count() }})</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">PRC License #</th>
                                <th class="px-4 py-2">PRC Expiry</th>
                                <th class="px-4 py-2">Specializations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($associateDentists as $dentist)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">
                                        Dr. {{ $dentist->first_name }} {{ $dentist->middle_initial ? $dentist->middle_initial . '.' : '' }} {{ $dentist->last_name }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $dentist->prc_license_number ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $dentist->prc_expiration_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if($dentist->specializations && $dentist->specializations->count())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($dentist->specializations as $spec)
                                                    <x-filament::badge color="info" size="sm">{{ $spec->name }}</x-filament::badge>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Service Fees --}}
        @if($basicServices->count() || $enhancementServices->count() || $specialServices->count())
            @php
                $hasPendingFees = $basicServices->contains(fn($s) => $s->pivot->new_fee) || $enhancementServices->contains(fn($s) => $s->pivot->new_fee) || $specialServices->contains(fn($s) => $s->pivot->new_fee);
            @endphp
            <x-filament::section icon="heroicon-o-currency-dollar">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        Service Fees
                        @if($hasPendingFees)
                            <x-filament::badge color="warning" size="sm">Pending Update</x-filament::badge>
                        @endif
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Basic Dental Services --}}
                    @if($basicServices->count())
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    🦷 Basic Dental Services
                                </h4>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($basicServices as $service)
                                    <div class="px-4 py-3 flex items-center justify-between gap-4 {{ $service->pivot->new_fee ? 'bg-warning-50 dark:bg-warning-950/20' : '' }}">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $service->name }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white {{ $service->pivot->new_fee ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                                ₱{{ number_format($service->pivot->fee, 2) }}
                                            </span>
                                            @if($service->pivot->new_fee)
                                                <x-heroicon-s-arrow-right class="w-3.5 h-3.5 text-warning-500" />
                                                <span class="text-sm font-bold text-warning-600 dark:text-warning-400">
                                                    ₱{{ number_format($service->pivot->new_fee, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $basicServices->count() }} services</span>
                                    <span>Total: ₱{{ number_format($basicServices->sum(fn($s) => $s->pivot->fee), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Plan Enhancements --}}
                    @if($enhancementServices->count())
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    ⭐ Plan Enhancements
                                </h4>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($enhancementServices as $service)
                                    <div class="px-4 py-3 flex items-center justify-between gap-4 {{ $service->pivot->new_fee ? 'bg-warning-50 dark:bg-warning-950/20' : '' }}">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $service->name }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white {{ $service->pivot->new_fee ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                                ₱{{ number_format($service->pivot->fee, 2) }}
                                            </span>
                                            @if($service->pivot->new_fee)
                                                <x-heroicon-s-arrow-right class="w-3.5 h-3.5 text-warning-500" />
                                                <span class="text-sm font-bold text-warning-600 dark:text-warning-400">
                                                    ₱{{ number_format($service->pivot->new_fee, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $enhancementServices->count() }} services</span>
                                    <span>Total: ₱{{ number_format($enhancementServices->sum(fn($s) => $s->pivot->fee), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Special Procedures --}}
                @if($specialServices->count())
                    <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                🏅 Special Procedures
                            </h4>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($specialServices as $service)
                                <div class="px-4 py-3 flex items-center justify-between gap-4 {{ $service->pivot->new_fee ? 'bg-warning-50 dark:bg-warning-950/20' : '' }}">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $service->name }}</p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white {{ $service->pivot->new_fee ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                            ₱{{ number_format($service->pivot->fee, 2) }}
                                        </span>
                                        @if($service->pivot->new_fee)
                                            <x-heroicon-s-arrow-right class="w-3.5 h-3.5 text-warning-500" />
                                            <span class="text-sm font-bold text-warning-600 dark:text-warning-400">
                                                ₱{{ number_format($service->pivot->new_fee, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $specialServices->count() }} services</span>
                                <span>Total: ₱{{ number_format($specialServices->sum(fn($s) => $s->pivot->fee), 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- Clinic Staff --}}
        @if($clinic->clinic_staff_name || $clinic->clinic_staff_mobile || $clinic->clinic_staff_email)
            <x-filament::section icon="heroicon-o-users" collapsible>
                <x-slot name="heading">Clinic Staff</x-slot>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_staff_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mobile</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_staff_mobile ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Viber</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_staff_viber ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->clinic_staff_email ?? '—' }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Bank Information --}}
        <x-filament::section icon="heroicon-o-banknotes" collapsible>
            <x-slot name="heading">Bank Information</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account Name</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->bank_account_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account Number</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->bank_account_number ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bank Name</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->bank_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Branch</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->bank_branch ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account Type</p>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->account_type ?? '—' }}</p>
                </div>
                @if($clinic->remarks)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</p>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $clinic->remarks }}</p>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-center py-8">
                <x-heroicon-o-building-office class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-gray-600 dark:text-gray-400">No clinic profile found.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
