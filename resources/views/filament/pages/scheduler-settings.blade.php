<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header note --}}
        @php $cronStatus = $this->cronStatus; @endphp
        <div class="rounded-xl ring-1 {{ $cronStatus['is_running'] ? 'ring-success-200 dark:ring-success-700/30 bg-success-50 dark:bg-success-900/10' : 'ring-danger-200 dark:ring-danger-700/30 bg-danger-50 dark:bg-danger-900/10' }} px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $cronStatus['is_running'] ? 'bg-success-100 dark:bg-success-900/30' : 'bg-danger-100 dark:bg-danger-900/30' }}">
                    @if($cronStatus['is_running'])
                        <svg style="color: #16a34a; width:1.25rem; height:1.25rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg style="color: #dc2626; width:1.25rem; height:1.25rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-semibold {{ $cronStatus['is_running'] ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        {{ $cronStatus['is_running'] ? 'Scheduler is running' : 'Scheduler not detected' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $cronStatus['last_ping'] ? 'Last heartbeat: ' . \Carbon\Carbon::parse($cronStatus['last_ping'])->diffForHumans() : 'No heartbeat received yet.' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-filament::button wire:click="pingNow" size="sm" color="gray" icon="heroicon-m-signal">
                    Ping Now
                </x-filament::button>
            </div>
        </div>

        {{-- Server setup instructions if not running --}}
        @if(!$cronStatus['is_running'])
        <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 px-6 py-4 space-y-2">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <x-heroicon-o-command-line class="h-4 w-4" />
                Ensure the scheduler is running on the server
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Add this cron entry via <code class="bg-gray-100 dark:bg-white/10 px-1 rounded">crontab -e</code>:</p>
            <pre class="text-xs bg-gray-100 dark:bg-white/5 rounded-lg px-4 py-3 text-gray-700 dark:text-gray-300 overflow-x-auto">* * * * * cd /var/www/html/ivory/current && php artisan schedule:run >> /dev/null 2>&1</pre>
            <p class="text-xs text-gray-500 dark:text-gray-400">Or if using Supervisor with <code class="bg-gray-100 dark:bg-white/10 px-1 rounded">schedule:work</code>, ensure the process is running.</p>
        </div>
        @endif

        {{-- Tasks --}}
        @php $tasks = \App\Models\ScheduleSetting::all(); @endphp

        <form wire:submit="save">
            <div class="space-y-4">
                @foreach($tasks as $task)
                @php $key = $task->id; @endphp

                <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
                     x-data="{ get enabled() { return $wire.get('settings.{{ $key }}.enabled') }, set enabled(val) { $wire.set('settings.{{ $key }}.enabled', val) } }">

                    {{-- Task header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors"
                                :class="enabled ? 'bg-success-100 dark:bg-success-900/30' : 'bg-gray-100 dark:bg-white/5'">
                                <svg :style="enabled ? 'color:#16a34a' : 'color:#9ca3af'" style="width:1.25rem;height:1.25rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                                </svg>
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
                                x-on:click="enabled = !enabled"
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
