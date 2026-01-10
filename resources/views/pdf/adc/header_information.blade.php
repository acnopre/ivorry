<table class="header-table">
    <tr>
        <td><strong>Clinic:</strong></td>
        <td colspan="3">{{ $clinicDetails->clinic_name }}</td>
    </tr>
    <tr>
        <td><strong>Dentist:</strong></td>
        <td colspan="3">{{ $dentist['first_name'] }} {{ $dentist['last_name'] }}</td>
    </tr>
    <tr>
        <td><strong>BIR Registered Name:</strong></td>
        <td colspan="3">{{ $clinicDetails->registered_name }}</td>
    </tr>
    <tr>
        <td><strong>TIN:</strong></td>
        <td>{{ $clinicDetails->tax_identification_no }}</td>
    </tr>
    <tr>
        <td><strong>Branch:</strong></td>
        <td>{{ $clinicDetails->is_branch == 1 ? 'YES' : 'NO' }}</td>
    </tr>
    <tr>
        <td><strong>Address:</strong></td>
        <td colspan="3">{{ $clinicDetails->complete_address }}</td>
    </tr>
    <tr>
        <td><strong>Vat Type:</strong></td>
        <td>{{ $clinicDetails->vat_type }}</td>
    </tr>
    <tr>
        <td><strong>EWT:</strong></td>
        <td>{{ $clinicDetails->withholding_tax }}</td>
    </tr>
</table>
