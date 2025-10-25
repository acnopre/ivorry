<x-filament-panels::page>
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-semibold">Sign Procedure</h2>
            <p class="text-gray-500">Please review the details and collect all required signatures below.</p>
        </div>

        {{-- 🦷 Procedure Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 bg-white rounded-xl shadow-sm border">
                <h3 class="font-semibold mb-3">Procedure Details</h3>
                <dl class="divide-y divide-gray-200">
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Member</dt>
                        <dd class="font-medium">{{ $record->member->full_name ?? '—' }}</dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Service</dt>
                        <dd class="font-medium">{{ $record->service->name ?? '—' }}</dd>
                    </div>
                    <div class="py-2 flex justify-between">
                        <dt class="text-gray-600">Availment Date</dt>
                        <dd class="font-medium">{{ optional($record->availment_date)->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- 🔳 QR Verification --}}
            <div class="flex flex-col items-center justify-center space-y-4">
                <h3 class="font-semibold">QR Verification</h3>

                @if ($record->qr_code_path)
                <img src="{{ Storage::url($record->qr_code_path) }}" alt="QR Code" class="w-48 h-48 rounded-lg border" />
                @else
                <div class="p-4 bg-white rounded-xl shadow-sm border">
                    {!! QrCode::size(200)->generate(route('filament.admin.resources.procedures.sign', $record)) !!}
                </div>
                @endif

                <p class="text-sm text-gray-500 text-center">Scan to verify this procedure or open on mobile.</p>
            </div>
        </div>

        {{-- 🖊️ Signature Pads --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border">
            <h3 class="font-semibold text-lg mb-4 text-center">Signatures</h3>

            {{-- Checkbox: Member unavailable --}}
            <div class="flex items-center justify-center mb-4">
                <input type="checkbox" id="member-unavailable" class="mr-2" onchange="toggleMemberAvailability()">
                <label for="member-unavailable" class="text-sm text-gray-600">Member not available — Dentist will sign on behalf</label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="signature-section">
                {{-- Dentist Signature --}}
                <div class="space-y-3 text-center">
                    <h4 class="font-semibold">Dentist</h4>
                    <canvas id="dentist-signature" class="border rounded-lg w-full h-40 bg-gray-50"></canvas>
                    <x-filament::button color="gray" size="sm" onclick="clearSignature('dentist-signature')">
                        Clear
                    </x-filament::button>
                </div>

                {{-- Member Signature --}}
                <div class="space-y-3 text-center member-signature">
                    <h4 class="font-semibold">Member Signature</h4>
                    <canvas id="member-signature" class="border rounded-lg w-full h-40 bg-gray-50"></canvas>
                    <x-filament::button color="gray" size="sm" onclick="clearSignature('member-signature')">
                        Clear
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- ✅ Submit --}}
        <div class="text-center">
            <x-filament::button color="success" wire:click="signNow" id="submit-signatures">
                Submit Signatures
            </x-filament::button>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        const pads = {
            dentist: new SignaturePad(document.getElementById('dentist-signature'))
            , member: new SignaturePad(document.getElementById('member-signature'))
        , };

        function clearSignature(id) {
            const key = id.split('-')[0];
            if (pads[key]) pads[key].clear();
        }

        function toggleMemberAvailability() {
            const unavailable = document.getElementById('member-unavailable').checked;
            document.querySelectorAll('.member-signature').forEach(el => {
                el.style.display = unavailable ? 'none' : 'block';
            });
        }

        document.getElementById('submit-signatures').addEventListener('click', () => {
            @this.set('signatures', {
                dentist: pads.dentist.isEmpty() ? null : pads.dentist.toDataURL()
                , member: pads.member.isEmpty() ? null : pads.member.toDataURL()
                , memberUnavailable: document.getElementById('member-unavailable').checked
            , });
        });

    </script>
    @endpush
</x-filament-panels::page>
