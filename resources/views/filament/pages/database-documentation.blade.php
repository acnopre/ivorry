<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $schema = $data['schema'];
    @endphp

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Database Documentation</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $data['totalTables'] }} tables</span>,
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $data['totalColumns'] }} columns</span>
                @if($data['cachedAt'])
                    &mdash; <span class="text-xs">cached {{ $data['cachedAt'] }}</span>
                @endif
            </p>
        </div>
        <div class="w-full md:w-72">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search tables..."
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
        </div>
    </div>

    {{-- Table Index --}}
    @if(blank($this->search))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">All Tables</p>
        <div class="flex flex-wrap gap-2">
            @foreach($schema->keys() as $table)
            <a href="#table-{{ $table }}"
               class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-700 dark:hover:text-primary-300 transition">
                {{ $table }}
                <span class="ml-1.5 text-gray-400 dark:text-gray-500">{{ $schema[$table]['columns']->count() }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tables --}}
    <div class="space-y-4">
        @forelse($schema as $table => $info)
        <div id="table-{{ $table }}" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">

            {{-- Table Header --}}
            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-table-cells class="w-4 h-4 text-primary-500 shrink-0" />
                    <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $table }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $info['columns']->count() }} columns</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                    @if($info['foreign']->count())
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-link class="w-3 h-3" />{{ $info['foreign']->count() }} FK
                    </span>
                    @endif
                    @if($info['indexes']->count())
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-bolt class="w-3 h-3" />{{ $info['indexes']->count() }} idx
                    </span>
                    @endif
                </div>
            </div>

            {{-- Columns --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="text-left px-5 py-2 font-medium w-8"></th>
                            <th class="text-left px-3 py-2 font-medium">Column</th>
                            <th class="text-left px-3 py-2 font-medium">Type</th>
                            <th class="text-left px-3 py-2 font-medium">Nullable</th>
                            <th class="text-left px-3 py-2 font-medium">Default</th>
                            <th class="text-left px-3 py-2 font-medium">Extra</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($info['columns'] as $col)
                        @php
                            $isPrimary = $info['primary'] && in_array($col['name'], $info['primary']['columns'] ?? []);
                            $fk = $info['foreign']->first(fn($f) => in_array($col['name'], $f['columns'] ?? []));
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-5 py-2 text-center">
                                @if($isPrimary)
                                    <span title="Primary Key" class="text-yellow-500">🔑</span>
                                @elseif($fk)
                                    <span title="Foreign Key → {{ $fk['foreign_table'] }}" class="text-blue-400">🔗</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono font-medium text-gray-800 dark:text-gray-200">
                                {{ $col['name'] }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-mono
                                    @if(str_contains($col['type'], 'int')) bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300
                                    @elseif(str_contains($col['type'], 'varchar') || str_contains($col['type'], 'text')) bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300
                                    @elseif(str_contains($col['type'], 'date') || str_contains($col['type'], 'time')) bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300
                                    @elseif(str_contains($col['type'], 'decimal') || str_contains($col['type'], 'float')) bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300
                                    @elseif(str_contains($col['type'], 'enum')) bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300
                                    @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400
                                    @endif">
                                    {{ $col['type'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($col['nullable'])
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">NULL</span>
                                @else
                                    <span class="text-red-500 dark:text-red-400 text-xs font-medium">NOT NULL</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $col['default'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                                @if($col['auto_increment'])
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 font-medium">AUTO_INCREMENT</span>
                                @endif
                                @if($fk)
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 font-mono">
                                        → {{ $fk['foreign_table'] }}.{{ implode(', ', $fk['foreign_columns'] ?? []) }}
                                    </span>
                                @endif
                                @if($col['comment'])
                                    <span class="text-gray-400">{{ $col['comment'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <x-heroicon-o-magnifying-glass class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">No tables found matching "<strong>{{ $this->search }}</strong>"</p>
        </div>
        @endforelse
    </div>

</x-filament-panels::page>
