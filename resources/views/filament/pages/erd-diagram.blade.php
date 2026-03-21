<x-filament-panels::page>
    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700" style="height: calc(100vh - 12rem);">
        <iframe
            src="{{ url('laravel-erd') }}"
            class="w-full h-full"
            frameborder="0"
            title="ERD Diagram"
        ></iframe>
    </div>
</x-filament-panels::page>
