@php
    $title     = 'Import Documentation';
    $subtitle  = 'Step-by-step guide for bulk importing Accounts, Members, Clinics and Procedures via Excel';
    $coverMeta = [
        ['value' => '4',          'label' => 'Import Types'],
        ['value' => 'XLS / XLSX', 'label' => 'Supported Formats'],
        ['value' => $generatedAt, 'label' => 'Generated'],
    ];
@endphp

@extends('pdf.doc-layout', compact('title','subtitle','coverMeta','generatedAt'))

@section('body')

{{-- Quick Start --}}
<div class="section-heading">
    <h2>Quick Start Guide</h2>
</div>

<div class="two-col">
    <div class="col-left">
        <div class="callout callout-blue">
            <div class="callout-title">Import Order</div>
            <ol>
                <li>Import <strong>Accounts</strong> first</li>
                <li>Then import <strong>Members</strong></li>
                <li>Then import <strong>Clinics</strong></li>
                <li>Finally import <strong>Procedures</strong></li>
            </ol>
        </div>
    </div>
    <div class="col-right">
        <div class="callout callout-green">
            <div class="callout-title">File Format Requirements</div>
            <ul>
                <li>Supported: <strong>.xlsx</strong>, <strong>.xls</strong></li>
                <li>First row must be column headers</li>
                <li>Date format: <code>YYYY-MM-DD</code> or Excel date</li>
                <li>Processed in chunks of 500 rows</li>
            </ul>
        </div>
    </div>
</div>

<div class="callout callout-yellow">
    <div class="callout-title">Important Notes</div>
    <ul>
        <li>Always backup the database before large imports</li>
        <li>Test with 5–10 rows first before full import</li>
        <li>Use <strong>Migration Mode</strong> only for initial data migration (requires <code>account.import.migration-mode</code> permission)</li>
        <li>Check Import Logs for detailed row-level results after each import</li>
    </ul>
</div>

{{-- Access Locations --}}
<div class="section-heading">
    <h2>Access Locations</h2>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Import Type</th><th>Navigation Path</th></tr></thead>
    <tbody>
        <tr><td><strong>Account Import</strong></td><td>Accounts &amp; Members → Accounts → Import XLS button</td></tr>
        <tr><td><strong>Member Import</strong></td><td>Accounts &amp; Members → Members → Import XLS button</td></tr>
        <tr><td><strong>Clinic Import</strong></td><td>Dental Management → Clinic Details → Import XLS button</td></tr>
        <tr><td><strong>Procedure Import</strong></td><td>Imports → Import Logs → Import Procedures button</td></tr>
    </tbody>
</table>

{{-- Account Import --}}
<div class="section-heading">
    <h2>Account Import</h2>
    <p>Navigation: Accounts &amp; Members → Accounts → Import XLS</p>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Column</th><th style="width:15%">Required</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['company_name',          'Yes', 'Name of the corporate client'],
            ['policy_code',           'Yes', 'Unique policy reference code'],
            ['hip',                   'No',  'Health Insurance Provider name'],
            ['card_used',             'No',  'Card type used'],
            ['effective_date',        'Yes', 'Coverage start date (YYYY-MM-DD)'],
            ['expiration_date',       'No',  'Auto-calculated if blank (1 year - 1 day)'],
            ['plan_type',             'Yes', 'INDIVIDUAL or SHARED'],
            ['coverage_period_type',  'Yes', 'ACCOUNT or MEMBER'],
            ['mbl_type',              'Yes', 'Procedural or Fixed'],
            ['mbl_amount',            'No',  'Required when mbl_type is Fixed'],
            ['endorsement_type',      'No',  'NEW (default), RENEWAL, or AMENDMENT'],
        ] as [$col,$req,$desc])
        <tr>
            <td><code>{{ $col }}</code></td>
            <td style="text-align:center">
                @if($req === 'Yes') <span class="badge badge-notnull">Required</span>
                @else <span class="badge badge-null">Optional</span> @endif
            </td>
            <td>{{ $desc }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Member Import --}}
<div class="section-heading">
    <h2>Member Import</h2>
    <p>Navigation: Accounts &amp; Members → Members → Import XLS</p>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Column</th><th style="width:15%">Required</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['policy_code',    'Yes', 'Must match an existing account policy code'],
            ['card_number',    'No',  'Unique card number (or use coc_number)'],
            ['coc_number',     'No',  'Alternative to card_number'],
            ['first_name',     'Yes', 'Member first name'],
            ['last_name',      'Yes', 'Member last name'],
            ['middle_name',    'No',  'Member middle name'],
            ['suffix',         'No',  'e.g. Jr., Sr.'],
            ['member_type',    'Yes', 'Principal or Dependent'],
            ['birthdate',      'No',  'Date of birth (YYYY-MM-DD)'],
            ['gender',         'No',  'Male or Female'],
            ['status',         'No',  'Active (default) or Inactive'],
            ['effective_date', 'No',  'Required when account Coverage Period Type is Member'],
        ] as [$col,$req,$desc])
        <tr>
            <td><code>{{ $col }}</code></td>
            <td style="text-align:center">
                @if($req === 'Yes') <span class="badge badge-notnull">Required</span>
                @else <span class="badge badge-null">Optional</span> @endif
            </td>
            <td>{{ $desc }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Clinic Import --}}
<div class="section-heading">
    <h2>Clinic Import</h2>
    <p>Navigation: Dental Management → Clinic Details → Import XLS</p>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Column</th><th style="width:15%">Required</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['clinic_name',          'Yes', 'Name on signage'],
            ['registered_name',      'No',  'BIR registered name'],
            ['region',               'No',  'Region name'],
            ['province',             'No',  'Province name'],
            ['municipality',         'No',  'City or municipality name'],
            ['barangay',             'No',  'Barangay name'],
            ['street',               'No',  'Street address'],
            ['clinic_mobile',        'No',  'Mobile number'],
            ['clinic_email',         'No',  'Email address'],
            ['accreditation_status', 'No',  'ACTIVE, INACTIVE, SILENT, SPECIFIC HIP, SPECIFIC ACCOUNT'],
            ['vat_type',             'No',  'VAT classification'],
            ['business_type',        'No',  'Business classification'],
            ['tin',                  'No',  'Tax Identification Number'],
        ] as [$col,$req,$desc])
        <tr>
            <td><code>{{ $col }}</code></td>
            <td style="text-align:center">
                @if($req === 'Yes') <span class="badge badge-notnull">Required</span>
                @else <span class="badge badge-null">Optional</span> @endif
            </td>
            <td>{{ $desc }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Procedure Import --}}
<div class="section-heading">
    <h2>Procedure Import</h2>
    <p>Navigation: Imports → Import Logs → Import Procedures</p>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Column</th><th style="width:15%">Required</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['card_number',     'Yes', 'Member card number or COC number'],
            ['clinic_name',     'Yes', 'Must match an existing accredited clinic'],
            ['service_name',    'Yes', 'Must match an existing service name'],
            ['availment_date',  'Yes', 'Date of procedure (YYYY-MM-DD)'],
            ['quantity',        'No',  'Number of units (default: 1)'],
            ['applied_fee',     'No',  'Fee applied (required for Fixed MBL accounts)'],
            ['approval_code',   'No',  'Existing approval code (auto-generated if blank)'],
            ['adc_number',      'No',  'ADC reference number for migrated records'],
            ['status',          'No',  'pending, signed, valid, processed (default: processed for migration)'],
        ] as [$col,$req,$desc])
        <tr>
            <td><code>{{ $col }}</code></td>
            <td style="text-align:center">
                @if($req === 'Yes') <span class="badge badge-notnull">Required</span>
                @else <span class="badge badge-null">Optional</span> @endif
            </td>
            <td>{{ $desc }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Import Logs --}}
<div class="section-heading">
    <h2>Import Logs</h2>
    <p>Navigation: Imports → Import Logs</p>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Column</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['Filename',     'Original uploaded file name'],
            ['Import Type',  'account, member, clinic, or procedure'],
            ['Batch Status', 'active or deleted'],
            ['Status',       'processing, partial, failed, or completed'],
            ['Total Rows',   'Total rows in the uploaded file'],
            ['Success Rows', 'Rows successfully imported'],
            ['Skipped Rows', 'Rows skipped (duplicates, already exists)'],
            ['Error Rows',   'Rows that failed validation'],
            ['Imported By',  'User who performed the import'],
            ['Created At',   'Import timestamp'],
        ] as [$col,$desc])
        <tr><td><strong>{{ $col }}</strong></td><td>{{ $desc }}</td></tr>
        @endforeach
    </tbody>
</table>

<div class="callout callout-blue">
    <div class="callout-title">Import Log Actions</div>
    <ul>
        <li><strong>View Details</strong> — See row-level import results with error messages (requires <code>import-logs.details.view</code>)</li>
        <li><strong>Delete Batch</strong> — Soft-delete all records from this import (requires <code>import.batch.delete</code>)</li>
        <li><strong>Restore Batch</strong> — Restore soft-deleted records (requires <code>import.batch.restore</code>)</li>
    </ul>
</div>

@endsection
