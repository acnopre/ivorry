<div class="space-y-4 py-2">

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-medium w-1/3">Version</td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-semibold">{{ $record->version }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-medium">Released At</td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $record->released_at->format('F d, Y h:i A') }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-medium align-top">Release Notes</td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                        @if ($record->notes)
                            {!! nl2br(e($record->notes)) !!}
                        @else
                            <span class="text-gray-400 italic">—</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
