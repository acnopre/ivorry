<div class="space-y-4">
    <x-filament::badge :color="$row->status === 'success' ? 'success' : 'danger'">
        {{ strtoupper($row->status) }}
    </x-filament::badge>

    @if($row->message)
    <div class="text-danger">
        <strong>Error:</strong> {{ $row->message }}
    </div>
    @endif

    <pre class="bg-gray-900 text-green-400 p-4 rounded text-xs overflow-auto">
    {{ json_encode($row->raw_data, JSON_PRETTY_PRINT) }}
    </pre>
</div>
