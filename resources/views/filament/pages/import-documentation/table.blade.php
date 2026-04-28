<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-50 dark:bg-gray-800 text-left">
            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300 w-1/4">Column</th>
            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300 w-1/6">Required</th>
            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Description</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($rows as [$col, $required, $desc])
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
            <td class="px-4 py-2">
                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $col }}</code>
            </td>
            <td class="px-4 py-2">
                @if($required)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">Required</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Optional</span>
                @endif
            </td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $desc }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
