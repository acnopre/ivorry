<div class="space-y-6 p-4">
    <h3 class="text-lg font-bold">Claim Summary</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fi-section border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <h4 class="text-md font-semibold mb-2">Claim Details</h4>
            <ul class="space-y-1 text-sm">
                <li class="flex items-center gap-2">
                    <span class="font-medium">Status:</span>
                    <x-filament::badge class="!inline !w-auto px-2 py-0.5 text-xs" :color="match ($record->status) {
                            'approved' => 'info',
                            'pending' => 'warning',
                            'valid' => 'success',
                            'invalid' => 'danger',
                            default => 'warning'
                        }">
                        {{ ucfirst($record->status) }}
                    </x-filament::badge>
                </li>
                <li><span class="font-medium">Availment Date:</span> {{ \Carbon\Carbon::parse($record->availment_date)->toFormattedDateString() }}</li>
                <li><span class="font-medium">Service:</span> {{ $record->service?->name ?? 'N/A' }}</li>
                <li><span class="font-medium">Approval Code:</span> {{ $record->approval_code ?? 'N/A' }}</li>
                @if($record->remarks)
                <li><span class="font-medium text-danger-600">Remarks:</span> {{ $record->remarks }}</li>
                @endif
            </ul>
        </div>

        <div class="fi-section border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <h4 class="text-md font-semibold mb-2">Member & Clinic</h4>
            <ul class="space-y-1 text-sm">
                <li><span class="font-medium">Member Name:</span> {{ $member?->first_name . ' ' . $member->last_name ?? 'N/A' }}</li>
                <li><span class="font-medium">Clinic:</span> {{ $record->clinic?->clinic_name ?? 'N/A' }}</li>
                <li><span class="font-medium">Account HIP:</span> {{ $account?->hip ?? 'N/A' }}</li>
            </ul>
        </div>
    </div>

    <div class="fi-section border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <h4 class="text-md font-semibold mb-2">Account Services (From Account: {{ $account?->policy_code ?? 'N/A' }})</h4>
        @if($services->count())
        <div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-950/5 bg-white dark:bg-gray-900">
            <table class="min-w-full text-sm text-left border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-2 font-medium">Service Name</th>
                        <th class="px-4 py-2 font-medium">Quantity</th>
                        <th class="px-4 py-2 font-medium">Is Unlimited</th>
                        <th class="px-4 py-2 font-medium">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($services as $service)
                    @if($service->pivot->quantity !=0)
                    <tr>
                        <td class="px-4 py-2">{{ $service->name ?? 'Service Name Missing' }}</td>
                        <td class="px-4 py-2">
                            @if($service->pivot->is_unlimited)
                            —
                            @else
                            {{ number_format($service->pivot->quantity ?? 0) }} / {{ number_format($service->pivot->default_quantity ?? 0) }}
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($service->pivot->is_unlimited)
                            Yes
                            @else
                            No
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                            {{ $service->pivot->remarks ?? '—' }}
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-center text-gray-500">No services assigned.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500">No services found for this account.</p>
        @endif
    </div>
    <div class="fi-section border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <h4 class="text-md font-semibold mb-2">Procedure Units</h4>
        @if($units->count())
        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left">Unit</th>
                    <th class="px-4 py-2 text-left">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($units as $unit)
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-2">
                        {{ $unit->unitType->name ?? 'N/A' }}:
                        {{ $unit->name ?? 'N/A' }}

                        @isset($unit->pivot->surface)
                        — Surface: {{ $unit->pivot->surface->name }}
                        @endisset
                    </td>

                    <td class="px-4 py-2">
                        {{ $unit->pivot->quantity ?? 'N/A' }}
                    </td>
                </tr>
                @endforeach


            </tbody>
        </table>
        @else
        <p class="text-sm text-gray-500">No procedure units found for this claim.</p>
        @endif
    </div>
    @if ($record->signatures && $record->signatures->isNotEmpty())
    @foreach ($record->signatures as $data)
    <div class="mt-4 p-4 border rounded-lg bg-gray-50">
        <h4 class="font-semibold mb-2 capitalize">
            {{ str_replace('_', ' ', $data->signer_type) }} Signature:
        </h4>

        @if (!empty($data->signature_path))
        <img src="{{ Storage::url($data->signature_path) }}" alt="{{ $data->signer_type }} Signature" class="h-32 border rounded shadow-sm">
        @else
        <p class="text-gray-500 italic">No signature image uploaded.</p>
        @endif

        {{-- Optional: show signer info if available --}}
        @if (!empty($data->signer_name) || !empty($data->created_at))
        <div class="mt-2 text-sm text-gray-600">
            @if (!empty($data->signer_name))
            <p><strong>Signed by:</strong> {{ $data->signer_name }}</p>
            @endif
            @if (!empty($data->created_at))
            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($data->created_at)->format('F d, Y h:i A') }}</p>
            @endif
        </div>
        @endif
    </div>
    @endforeach
    @else
    <div class="fi-section border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <h4 class="text-md font-semibold mb-2">Signatures</h4>
        <p class="text-gray-500">No signatures recorded yet.</p>
    </div>
    @endif


</div>
