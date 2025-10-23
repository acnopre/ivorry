<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 🔍 Search Form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- 🏢 Clinic Results --}}
        @if($clinics->isNotEmpty())
        <div class="space-y-6">
            @foreach($clinics as $clinic)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl p-6 border-l-4 border-primary-500 space-y-4">

                {{-- 🏷 Clinic Header --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 mb-4 dark:border-gray-700">
                    <div class="space-y-1">
                        <div class="font-bold text-2xl text-gray-900 dark:text-white">{{ $clinic->clinic_name }}</div>
                        <div class="text-base text-gray-600 dark:text-gray-400">
                            <span class="font-semibold">Dentist Owner:</span> {{ $clinic->dentists->first()?->full_name ?? '-' }}
                        </div>
                    </div>
                </div>

                {{-- 🗺 Clinic Info --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Address</div>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($clinic->street.' '.$clinic->city.' '.$clinic->province.' '.$clinic->region) }}" target="_blank" class="text-blue-600 hover:underline">
                            {{ $clinic->street }}, {{ $clinic->city }}, {{ $clinic->province }}, {{ $clinic->region }}
                        </a>
                    </div>
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Contact</div>
                        <a href="tel:{{ $clinic->clinic_mobile ?? $clinic->clinic_landline }}" class="text-blue-600 hover:underline">
                            {{ $clinic->clinic_mobile ?? $clinic->clinic_landline ?? '-' }}
                        </a>
                    </div>
                    <div>
                        <div class="font-medium text-gray-500 dark:text-gray-400">Specializations</div>
                        <div class="text-gray-900 dark:text-white">
                            {{ $clinic->dentists->pluck('specializations')->flatten()->pluck('name')->unique()->join(', ') ?: '-' }}
                        </div>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
        @else
        {{-- 🕵️ No Results --}}
        <div class="text-center text-gray-500 py-8 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-700">
            <svg class="w-8 h-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9A6 6 0 006 9c0 5 6 9 6 9s6-4 6-9zM10.5 9.75a.75.75 0 111.5 0 .75.75 0 01-1.5 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No clinics found</h3>
            <p class="mt-1 text-sm text-gray-500">Please adjust your filters or contact admin.</p>
        </div>
        @endif

    </div>
</x-filament-panels::page>
