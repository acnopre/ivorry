<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 🔍 Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- 🏥 Clinic Search Results --}}
        @if ($clinics->isNotEmpty())
        <div class="space-y-6">
            @foreach ($clinics as $clinic)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6 border-l-4 border-primary-500 transition hover:shadow-xl">

                {{-- 🏷 Clinic Header --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <div class="space-y-1">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white">
                            {{ $clinic->clinic_name }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold">Dentist Owner:</span>
                            @foreach($clinic->dentists as $dentist)
                            @if($dentist->is_owner)
                            {{ trim(($dentist->first_name ?? '') . ' ' . ($dentist->last_name ?? '')) ?: 'Not specified' }}

                            @endif
                            @endforeach
                        </p>
                    </div>
                    <div class="mt-3 sm:mt-0">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($clinic->street.' '.$clinic->city.' '.$clinic->province.' '.$clinic->region) }}" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            <x-heroicon-o-map-pin class="w-5 h-5 mr-1" />
                            View on Map
                        </a>
                    </div>
                </div>

                {{-- 🗺 Clinic Information --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    {{-- Address --}}
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Address</div>
                        <div class="text-gray-900 dark:text-white">
                            {{ $clinic->street }}, {{ $clinic->city }}, {{ $clinic->province }}, {{ $clinic->region }}
                        </div>
                    </div>

                    {{-- Contact Info --}}
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Contact</div>
                        @php
                        $contact = $clinic->clinic_mobile ?? $clinic->clinic_landline;
                        @endphp
                        @if ($contact)
                        <a href="tel:{{ $contact }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ $contact }}
                        </a>
                        @else
                        <span class="text-gray-400">No contact info available</span>
                        @endif
                    </div>

                    {{-- Specializations --}}
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Specializations</div>
                        @php
                        $specializations = $clinic->dentists->pluck('specializations')->flatten()->pluck('name')->unique();
                        @endphp
                        @if ($specializations->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach ($specializations as $spec)
                            <span class="inline-block bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200 text-xs font-medium px-2.5 py-1 rounded-full">
                                {{ $spec }}
                            </span>
                            @endforeach
                        </div>
                        @else
                        <span class="text-gray-400">No specializations listed</span>
                        @endif
                    </div>
                </div>

            </div>
            @endforeach
        </div>
        @else
        {{-- 🕵️ No Results --}}
        <div class="text-center text-gray-500 py-8 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-700"> <svg class="w-8 h-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <x-heroicon-o-magnifying-glass class="w-10 h-10 mx-auto text-gray-400" />
                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white"> No Clinics Found </h3>
                <p class="mt-1 text-sm text-gray-500"> We couldn't find any clinics matching your criteria. </p>
        </div> @endif
    </div>
</x-filament-panels::page>
