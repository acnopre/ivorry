<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header note --}}
        @php $cronStatus = $this->cronStatus; @endphp
        <div class="rounded-xl ring-1 {{ $cronStatus['is_running'] ? 'ring-success-200 dark:ring-success-700/30 bg-success-50 dark:bg-success-900/10' : 'ring-danger-200 dark:ring-danger-700/30 bg-danger-50 dark:bg-danger-900/10' }} px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $cronStatus['is_running'] ? 'bg-success-100 dark:bg-success-900/30' : 'bg-danger-100 dark:bg-danger-900/30' }}">
                    @if($cronStatus['is_running'])
                        <x-heroicon-s-check-circle class="h-5 w-5 text-success-600 dark:text-success-400" />
                    @else
                        <x-heroicon-s-x-circle class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                    @endif
                </div>
                <div>
                    <p class="text-sm font-semibold {{ $cronStatus['is_running'] ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        {{ $cronStatus['is_running'] ? 'Scheduler is running' : 'Scheduler not detected' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $cronStatus['last_ping'] ? 'Last heartbeat: ' . \Carbon\Carbon::parse($cronStatus['last_ping'])->diffForHumans() : 'No heartbeat received yet — cron may not be configured.' }}
                    </p>
                </div>
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500">
                Changes take effect on the next scheduler cycle. Managed by <strong>Supervisor</strong>.
            </div>
        </div>

        {{-- Tasks --}}
        @php $tasks = \App\Models\ScheduleSetting::all(); @endphp

        <form wire:submit="save">
            <div class="space-y-4">
                @foreach($tasks as $task)
                @php $key = $task->id; @endphp

                <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
                     x-data="{ enabled: {{ json_encode((bool)($settings[$key]['enabled'] ?? $task->enabled)) }} }">

                    {{-- Task header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-colors"
                                :class="enabled ? 'bg-success-100 dark:bg-success-900/30' : 'bg-gray-100 dark:bg-white/5'">
                                <span :class="enabled ? 'text-success-600 dark:text-success-400' : 'text-gray-400'">
                                    <x-heroicon-s-clock class="h-4 w-4" />
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $task->label }}</span>
                                    <code class="text-xs bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded">{{ $task->command }}</code>
                                    <span :class="enabled ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400'"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium transition-colors"
                                        x-text="enabled ? 'Enabled' : 'Disabled'"></span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $task->description }}</p>
                            </div>
                        </div>

                        {{-- Run Now --}}
                        <x-filament::button
                            type="button"
                            wire:click="runNow({{ $key }})"
                            wire:loading.attr="disabled"
                            wire:target="runNow({{ $key }})"
                            size="sm"
                            icon="heroicon-m-play"
                            wire:loading.class="opacity-75">
                            <span wire:loading.remove wire:target="runNow({{ $key }})">Run Now</span>
                            <span wire:loading wire:target="runNow({{ $key }})">Running...</span>
                        </x-filament::button>
                    </div>

                    {{-- Settings --}}
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-3 items-center">

                        {{-- Enable toggle --}}
                        <div class="flex items-center gap-3">
                            <button type="button"
                                x-on:click="enabled = !enabled; $wire.set('settings.{{ $key }}.enabled', enabled)"
                                :aria-checked="enabled.toString()"
                                :class="enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-white/10'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2">
                                <span
                                    :class="enabled ? 'translate-x-5' : 'translate-x-0'"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="enabled ? 'Enabled' : 'Disabled'"></span>
                        </div>

                        {{-- Time picker (only for daily tasks) --}}
                        @if(!$task->isEveryMinute())
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 shrink-0">Run at:</label>
                            <input type="time"
                                wire:model="settings.{{ $key }}.daily_time"
                                class="rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm px-3 py-1.5 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        @else
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-m-arrow-path class="h-4 w-4" />
                            Runs every minute (not configurable)
                        </div>
                        @endif

                        {{-- Last run --}}
                        <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
                            @if($task->last_run_at)
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($task->last_run_status === 'success')
                                        <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-success-500" />
                                        <span>Last run: {{ $task->last_run_at->diffForHumans() }}</span>
                                    @else
                                        <x-heroicon-m-x-circle class="h-3.5 w-3.5 text-danger-500" />
                                        <span class="text-danger-500">Failed: {{ $task->last_run_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            @else
                                <span>Never run</span>
                            @endif
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

            {{-- Save button --}}
            <div class="mt-6 flex justify-end">
                <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    icon="heroicon-m-check"
                    wire:loading.class="opacity-75">
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-filament::button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
