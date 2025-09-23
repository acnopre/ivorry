<x-filament-widgets::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">User Activity Timeline</h2>

        <ul class="relative border-s border-gray-300 ms-3">
            @foreach ($this->getActivities() as $activity)
            <li class="mb-6 ms-6">
                <!-- Circle -->
                <span class="absolute -start-3 flex items-center justify-center w-6 h-6 bg-blue-500 rounded-full ring-8 ring-white"></span>

                <!-- User + Action -->
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-800">
                        {{ $activity->causer?->name ?? 'System' }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ $activity->created_at->diffForHumans() }}
                    </span>
                </div>

                <!-- Action details -->
                <p class="text-sm text-gray-600">
                    {{ ucfirst($activity->description) }}
                    @if($activity->subject_type)
                    on <span class="font-medium">{{ class_basename($activity->subject_type) }}</span>
                    @endif
                </p>

                <!-- Old/New values -->
                @if(isset($activity->properties['attributes']) || isset($activity->properties['old']))
                <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                    @if(isset($activity->properties['old']))
                    <p class="text-red-600">Old: {{ json_encode($activity->properties['old']) }}</p>
                    @endif
                    @if(isset($activity->properties['attributes']))
                    <p class="text-green-600">New: {{ json_encode($activity->properties['attributes']) }}</p>
                    @endif
                </div>
                @endif
            </li>
            @endforeach
        </ul>
    </x-filament::card>
</x-filament-widgets::widget>
