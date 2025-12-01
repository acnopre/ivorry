<x-filament-panels::page>
    <div class="space-y-6 max-w-4xl mx-auto">

        {{-- 📚 Header --}}
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-gray-800">Procedure Sign-off Sheet</h1>
            <p class="text-lg text-gray-500 mt-1">Please review details and complete the required signatures below.</p>
        </div>

        {{-- 1. Procedure & Verification Card --}}
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <h2 class="text-xl font-bold mb-4 border-b pb-2 text-primary-600">Procedure Details</h2>
                <dl class="text-base">
                    <div class="py-3 flex justify-between border-b border-gray-100">
                        <dt class="text-gray-600 font-medium">Approval Code</dt>
                        <dd class="font-extrabold text-gray-900">{{ $record->approval_code ?? '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between border-b border-gray-100">
                        <dt class="text-gray-600 font-medium">Member</dt>
                        <dd class="font-extrabold text-gray-900">{{ $record->member->full_name ?? '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between border-b border-gray-100">
                        <dt class="text-gray-600 font-medium">Service Performed</dt>
                        <dd class="font-bold text-gray-800">{{ $record->service->name ?? '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between">
                        <dt class="text-gray-600 font-medium">Date of Availment</dt>
                        <dd class="font-bold text-gray-800">{{ optional($record->availment_date)->format('F d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-col items-center justify-center space-y-3 p-4 bg-gray-50 rounded-xl">
                <h3 class="font-bold text-sm text-gray-700 uppercase tracking-wider">Verification Code</h3>
                @if ($record->qr_code_path)
                <img src="{{ Storage::url($record->qr_code_path) }}" alt="QR Code" class="w-36 h-36 rounded-lg shadow-md" />
                @else
                <div class="p-2 bg-white rounded-lg border shadow-md">
                    {!! QrCode::size(144)->generate(route('filament.admin.resources.procedures.sign', $record)) !!}
                </div>
                @endif
                <p class="text-xs text-gray-500 text-center mt-1">
                    Scan this code to open the verification page. You can share it with the member for scanning.
                </p>
            </div>
        </div>

        {{-- 2. Signature Panel --}}
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-6 text-center border-b pb-3 text-primary-600">Required Signatures</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8" id="signature-section">

                {{-- Member Signature --}}
                <div class="space-y-3 p-4 border rounded-xl bg-white shadow-sm member-signature 
                col-span-1 md:col-span-2 mx-auto max-w-md">

                    <h4 class="font-bold text-lg text-center text-gray-800">Patient/Member Signature</h4>
                    <p class="text-sm text-center text-gray-500">Please sign inside the box</p>

                    <canvas id="member-signature" class="border-2 border-dashed border-gray-300 rounded-lg w-full h-40 bg-white cursor-crosshair"></canvas>

                    <div class="flex justify-center">
                        <x-filament::button color="primary" size="sm" icon="heroicon-o-arrow-path" onclick="clearSignature('member-signature')">
                            Clear Signature
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="p-6 bg-gray-50 rounded-2xl text-center">
            <x-filament::button color="success" size="xl" wire:click="signNow" id="submit-signatures" class="w-full md:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="signNow">Complete & Submit Signatures</span>
                <span wire:loading wire:target="signNow">Processing...</span>
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    const memberCanvas = document.getElementById('member-signature');
    const submitButton = document.getElementById('submit-signatures');

    // Resize & initialize pad
    function resizeCanvas(canvas, pad) {
        if (!canvas) return;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const data = pad ? pad.toData() : null;

        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);

        if (pad && data) pad.fromData(data);
    }

    const pads = {
        member: new SignaturePad(memberCanvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        })
    };

    resizeCanvas(memberCanvas, pads.member);

    window.onresize = () => {
        resizeCanvas(memberCanvas, pads.member);
        validateSignatures();
    };

    // Clear signature
    window.clearSignature = function(id) {
        if (pads.member) pads.member.clear();
        validateSignatures();
    };

    // Validation (member signature required)
    function validateSignatures() {
        const signed = !pads.member.isEmpty();
        // submitButton.disabled = !signed;
    }

    pads.member.onEnd = validateSignatures;
    memberCanvas.addEventListener('mouseup', validateSignatures);
    memberCanvas.addEventListener('touchend', validateSignatures);

    // Submit
    submitButton.addEventListener('click', () => {
        @this.set('signatures', {
            member: pads.member.isEmpty() ? null : pads.member.toDataURL("image/png")
        , });
    });

    validateSignatures();

</script>
@endpush
