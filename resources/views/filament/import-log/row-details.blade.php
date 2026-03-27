<div class="space-y-4 py-2">

    {{-- Status banner --}}
    @php
        $banners = [
            'error'     => ['bg' => 'danger',  'label' => 'Error'],
            'duplicate' => ['bg' => 'warning', 'label' => 'Duplicate'],
            'updated'   => ['bg' => 'info',    'label' => 'Updated'],
            'success'   => ['bg' => 'success', 'label' => 'Success'],
        ];
        $b = $banners[$status] ?? ['bg' => 'success', 'label' => ucfirst($status)];

        $dateFields = ['effective_date', 'expiration_date', 'inactive_date', 'birthdate'];

        $formatValue = function($key, $value) use ($dateFields) {
            if (!in_array($key, $dateFields) || empty($value)) return $value;
            if (is_numeric($value)) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('M d, Y');
                } catch (\Exception $e) {
                    return $value;
                }
            }
            return $value;
        };
    @endphp

    <div class="rounded-lg bg-{{ $b['bg'] }}-50 border border-{{ $b['bg'] }}-200 px-4 py-3 text-{{ $b['bg'] }}-700 text-sm dark:bg-{{ $b['bg'] }}-950 dark:border-{{ $b['bg'] }}-800 dark:text-{{ $b['bg'] }}-400">
        <span class="font-semibold">{{ $b['label'] }}:</span> {{ $message ?? '—' }}
    </div>

    {{-- Row data --}}
    @php
        $rowData = is_string($data) ? (json_decode($data, true) ?? []) : ($data ?? []);
    @endphp
    @if (!empty($rowData))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300 w-1/3">Field</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($rowData as $key => $value)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200 break-all">
                                @php $formatted = $formatValue($key, $value); @endphp
                                @if (is_null($formatted) || $formatted === '')
                                    <span class="text-gray-400 italic">—</span>
                                @elseif (is_bool($formatted))
                                    <span class="{{ $formatted ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $formatted ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    {{ $formatted }}
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
