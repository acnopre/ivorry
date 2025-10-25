<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Procedure</title>
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h2 class="mb-3 text-center">Procedure Signature</h2>

    <div class="card shadow-sm p-4 mb-4">
      <h5>Procedure Details</h5>
      <p><strong>Clinic:</strong> {{ $procedure->clinic->clinic_name }}</p>
      <p><strong>Service:</strong> {{ $procedure->service->name }}</p>
      <p><strong>Availment Date:</strong> {{ $procedure->availment_date->format('M d, Y') }}</p>
      <p><strong>Approval Code:</strong> {{ $procedure->approval_code }}</p>
    </div>

    <div class="card shadow-sm p-4">
      <form method="POST" action="{{ route('procedure.sign.store', $procedure->approval_code) }}">
        @csrf

        <div class="mb-3">
          <label for="signer_name" class="form-label">Your Name</label>
          <input type="text" class="form-control" name="signer_name" id="signer_name" required>
        </div>

        <div class="mb-3 text-center">
          <canvas id="signature-pad" class="border border-dark rounded" width="400" height="200"></canvas>
        </div>

        <div class="text-center">
          <button type="button" id="clear" class="btn btn-secondary me-2">Clear</button>
          <button type="submit" id="save" class="btn btn-primary">Save Signature</button>
        </div>

        <input type="hidden" name="signature" id="signature">
      </form>
    </div>

    <div class="mt-4 text-center">
      <h5>Existing Signatures: {{ $procedure->signatures->count() }}/3</h5>
      <div class="d-flex justify-content-center flex-wrap gap-3">
        @foreach($procedure->signatures as $sig)
          <div>
            <img src="{{ asset('storage/' . $sig->signature_path) }}" width="150" class="border rounded shadow-sm">
            <p class="small mt-1">{{ $sig->signer_name }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <script>
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas);

    document.getElementById('clear').addEventListener('click', () => signaturePad.clear());

    document.getElementById('save').addEventListener('click', (e) => {
      if (signaturePad.isEmpty()) {
        alert('Please provide a signature first.');
        e.preventDefault();
      } else {
        document.getElementById('signature').value = signaturePad.toDataURL();
      }
    });
  </script>
</body>
</html>
