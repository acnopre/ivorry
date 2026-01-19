<x-filament::page>
    <h2 class="text-lg font-bold mb-4">Available Printers</h2>

    <table class="w-full table-auto border">
        <thead>
            <tr>
                <th class="border px-4 py-2">Printer Name</th>
                <th class="border px-4 py-2">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($printers as $printer)
            <tr>
                <td class="border px-4 py-2">{{ $printer['name'] }}</td>
                <td class="border px-4 py-2">{{ $printer['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-filament::page>
