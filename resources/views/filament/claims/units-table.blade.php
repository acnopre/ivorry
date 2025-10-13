<table class="w-full text-sm text-left border border-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-2 border">Unit Name</th>
            <th class="px-4 py-2 border">Unit Type</th>
            <th class="px-4 py-2 border">Quantity</th>
        </tr>
    </thead>
    <tbody>
        @forelse($units as $unit)
        <tr>
            <td class="px-4 py-2 border">{{ $unit->unit?->name ?? '-' }}</td>
            <td class="px-4 py-2 border">{{ $unit->unitType?->name ?? '-' }}</td>
            <td class="px-4 py-2 border">{{ $unit->quantity }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="px-4 py-2 border text-center">No units found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
