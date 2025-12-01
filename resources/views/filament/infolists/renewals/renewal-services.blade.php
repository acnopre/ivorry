@php
// Define your tabs
$tabs = [
'renewal_services' => 'Renewal Services',
];

@endphp
<div x-data="{ activeTab: 'renewal_services' }" class="w-full">
    {{-- Full-Width Tab Headers (Uses standard Filament styles for look and feel) --}}
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <nav class="-mb-px flex space-x-8 px-4 sm:px-6 lg:px-8" aria-label="Tabs">
            @foreach ($tabs as $key => $label)
            <a href="#" @click.prevent="activeTab = '{{ $key }}'" :class="{ 
                        'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400': activeTab === '{{ $key }}',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200': activeTab !== '{{ $key }}' 
                    }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition duration-150 ease-in-out">
                {{ $label }}
                {{-- Tab Badges --}}
                @if ($key === 'services')
                <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $services->count() }}</span>
                @endif
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content Containers --}}
    <div class="py-6">
        {{-- Services Tab Content --}}
        <div x-show="activeTab === 'renewal_services'" x-cloak>
            @include('filament.infolists.components.renewal-services-table', ['renewal_services' => $renewal_services])
        </div>
    </div>
</div>
