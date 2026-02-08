<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- Results Count --}}
        @if ($hasSearched)
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Found <span class="font-semibold text-gray-900 dark:text-white">{{ $clinics->count() }}</span> {{ Str::plural('clinic', $clinics->count()) }}
        </div>
        @endif

        {{-- Clinic Results --}}
        @if ($clinics->isNotEmpty())
        <div class="grid gap-6">
            @foreach ($clinics as $clinic)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden hover:shadow-md transition">
                <div class="p-6">
                    {{-- Header --}}
                    <div class="flex justify-between items-start gap-4 mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $clinic->clinic_name }}
                            </h3>
                            @php
                            $owner = $clinic->dentists->firstWhere('is_owner', true);
                            @endphp
                            @if($owner)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Owner:</span> {{ $owner->first_name }} {{ $owner->last_name }}
                            </p>
                            @endif
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($clinic->street.' '.$clinic->city.' '.$clinic->province.' '.$clinic->region) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">
                            <x-heroicon-o-map-pin class="w-4 h-4" />
                            Map
                        </a>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Address --}}
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Address</div>
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $clinic->street }}<br>
                                {{ $clinic->city }}, {{ $clinic->province }}<br>
                                {{ $clinic->region }}
                            </div>
                        </div>

                        {{-- Contact --}}
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contact</div>
                            @php
                            $contact = $clinic->clinic_mobile ?? $clinic->clinic_landline;
                            @endphp
                            @if ($contact)
                            <a href="tel:{{ $contact }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                {{ $contact }}
                            </a>
                            @else
                            <span class="text-sm text-gray-400">N/A</span>
                            @endif
                        </div>

                        {{-- Specializations --}}
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Specializations</div>
                            @php
                            $specializations = $clinic->dentists->pluck('specializations')->flatten()->pluck('name')->unique();
                            @endphp
                            @if ($specializations->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($specializations as $spec)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                    {{ $spec }}
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-sm text-gray-400">N/A</span>
                            @endif
                        </div>

                        {{-- Accreditation Status (CSR/Super Admin only) --}}
                        @if(auth()->user()->hasAnyRole([\App\Models\Role::SUPER_ADMIN, \App\Models\Role::CSR]))
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Accreditation</div>
                            <div class="text-sm">
                                @if($clinic->accreditation_status)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold 
                                                    @if(str_contains(strtolower($clinic->accreditation_status), 'active')) bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300
                                                    @elseif(str_contains(strtolower($clinic->accreditation_status), 'pending')) bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300
                                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                                    @endif">
                                    {{ $clinic->accreditation_status }}
                                </span>
                                @else
                                <span class="text-gray-400">N/A</span>
                                @endif
                            </div>
                        </div>

                        {{-- HIP (if specific HIP selected) --}}
                        @if($clinic->hip)
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">HIP</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ $clinic->hip->name }}
                            </div>
                        </div>
                        @endif
                        {{-- Account (if specific account selected) --}}
                        @if($clinic->account)
                        <div class="space-y-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Account</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ $clinic->account->company_name }}
                            </div>
                        </div>
                        @endif
                        @endif

                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @elseif($hasSearched)
        {{-- No Results --}}
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <x-heroicon-o-magnifying-glass class="w-8 h-8 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Clinics Found</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Try adjusting your search filters</p>
        </div>
        @endif
    </div>
</x-filament-panels::page>
