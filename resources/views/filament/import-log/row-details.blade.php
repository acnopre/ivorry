<div class="space-y-4 py-2">

    {{-- Status banner --}}
    @if ($status === 'error')
        <div class="rounded-lg bg-danger-50 border border-danger-200 px-4 py-3 text-danger-700 text-sm dark:bg-danger-950 dark:border-danger-800 dark:text-danger-400">
            <span class="font-semibold">Error:</span> {{ $message ?? 'An error occurred while processing this row.' }}
        </div>
    @else
        <div class="rounded-lg bg-success-50 border border-success-200 px-4 py-3 text-success-700 text-sm dark:bg-success-950 dark:border-success-800 dark:text-success-400">
            <span class="font-semibold">{{ $message ?? 'Imported' }}</span> — This row was processed successfully.
        </div>
    @endif

    {{-- Row data --}}
    @if (!empty($data))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300 w-1/3">Field</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($data as $key => $value)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400 capitalize">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200 break-all">
                                @if (is_null($value) || $value === '')
                                    <span class="text-gray-400 italic">—</span>
                                @elseif (is_bool($value))
                                    <span class="{{ $value ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $value ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-400 italic">No row data available.</p>
    @endif

</div>
