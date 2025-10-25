<?php

namespace App\Http\Controllers;

use App\Models\Procedure;
use App\Models\ProcedureSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    public function show($approval_code)
    {
        $procedure = Procedure::with(['member', 'clinic', 'service', 'signatures'])
            ->where('approval_code', $approval_code)
            ->firstOrFail();

        return view('signature.portal', compact('procedure'));
    }

    public function store(Request $request, $approval_code)
    {
        $request->validate([
            'signature' => 'required|string',
            'signer_name' => 'required|string|max:255',
        ]);

        $procedure = Procedure::where('approval_code', $approval_code)->firstOrFail();

        // Save base64 image as PNG file
        $image = $request->input('signature');
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $filename = 'signatures/' . uniqid() . '.png';
        Storage::disk('public')->put($filename, base64_decode($image));

        ProcedureSignature::create([
            'procedure_id' => $procedure->id,
            'signer_name' => $request->signer_name,
            'signer_type' => 'patient',
            'signature_path' => $filename,
        ]);

        // Check if all signatures are completed
        if ($procedure->signatures()->count() >= 3) {
            $procedure->update(['status' => Procedure::STATUS_COMPLETED]);
        }

        return redirect()->back()->with('success', 'Signature saved successfully!');
    }
}
