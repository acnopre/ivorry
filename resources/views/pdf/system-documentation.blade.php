@php
    $title     = 'System Documentation';
    $subtitle  = 'Complete user guide — modules, roles, workflows and business rules';
    $coverMeta = [
        ['value' => '9',          'label' => 'User Roles'],
        ['value' => '6',          'label' => 'Modules'],
        ['value' => $generatedAt, 'label' => 'Generated'],
    ];
@endphp

@extends('pdf.doc-layout', compact('title','subtitle','coverMeta','generatedAt'))

@section('body')

{{-- User Roles --}}
<div class="section-heading">
    <h2>User Roles &amp; Permissions</h2>
</div>
<table class="info-table">
    <thead><tr><th style="width:25%">Role</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['Super Admin',       'Full system access'],
            ['Upper Management',  'Approvals, reports, dashboard overview'],
            ['Middle Management', 'Account approvals'],
            ['Account Manager',   'Manage accounts and members'],
            ['Claims Processor',  'Process and print ADC claims'],
            ['Accreditation',     'Manage clinic accreditation'],
            ['CSR',               'Search members, add procedures, sign claims'],
            ['Dentist',           'View own procedures, sign procedures'],
            ['Member',            'View own profile and benefits'],
        ] as [$role, $desc])
        <tr><td><strong>{{ $role }}</strong></td><td>{{ $desc }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- Accounts --}}
<div class="section-heading">
    <h2>Accounts &amp; Members</h2>
    <p>Navigation: Accounts &amp; Members → Accounts / Members</p>
</div>

<div class="two-col">
    <div class="col-left">
        <div class="sub-heading">Account Information Fields</div>
        <table class="info-table">
            <thead><tr><th style="width:40%">Field</th><th>Description</th></tr></thead>
            <tbody>
                @foreach([
                    ['Company Name',        'Name of the corporate client'],
                    ['Policy Code',         'Unique identifier for the policy'],
                    ['HIP',                 'Health Insurance Provider'],
                    ['Plan Type',           'Individual — own MBL; Shared — shared pool'],
                    ['Coverage Period Type','Account — shared dates; Member — individual dates'],
                    ['MBL Type',            'Procedural — by quantity; Fixed — by peso amount'],
                    ['MBL Amount',          'Required only when MBL Type is Fixed'],
                ] as [$f,$d])
                <tr><td><strong>{{ $f }}</strong></td><td>{{ $d }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-right">
        <div class="sub-heading">Account Statuses</div>
        @foreach([
            ['Pending',  'Awaiting approval',          '#eab308'],
            ['Approved', 'Active and usable',           '#22c55e'],
            ['Rejected', 'Not approved',                '#ef4444'],
            ['Active',   'Within coverage period',      '#22c55e'],
            ['Inactive', 'Not yet active',              '#9ca3af'],
            ['Expired',  'Coverage has ended',          '#ef4444'],
        ] as [$s,$d,$color])
        <div class="status-row">
            <div class="status-dot"><span style="background:{{ $color }}"></span></div>
            <div class="status-name">{{ $s }}</div>
            <div class="status-desc">{{ $d }}</div>
        </div>
        @endforeach
    </div>
</div>

<div class="callout callout-blue">
    <div class="callout-title">Endorsement Types</div>
    <ul>
        <li><strong>NEW</strong> — Initial account creation</li>
        <li><strong>RENEWAL</strong> — Extends account for another period. Resets service quantities. Effective date must differ from current.</li>
        <li><strong>AMENDMENT</strong> — Modifies details, services, or members without changing the period</li>
    </ul>
</div>

<div class="sub-heading">Member Fields</div>
<table class="info-table">
    <thead><tr><th style="width:30%">Field</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['Account',        'Select an active account'],
            ['Card Number',    'Unique member card number'],
            ['COC Number',     'Alternative to card number (toggle "Enable COC Number")'],
            ['Member Type',    'Principal or Dependent'],
            ['Status',         'Active or Inactive'],
            ['Coverage Dates', 'Visible only when account Coverage Period Type is Member'],
        ] as [$f,$d])
        <tr><td><strong>{{ $f }}</strong></td><td>{{ $d }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- Dental Management --}}
<div class="section-heading">
    <h2>Dental Management</h2>
    <p>Navigation: Dental Management → My Procedures / Service Fee Approval</p>
</div>

<div class="sub-heading">Procedure Statuses</div>
<table class="info-table">
    <thead><tr><th style="width:20%">Status</th><th>Description</th></tr></thead>
    <tbody>
        @foreach([
            ['Pending',   'Submitted, awaiting dentist signature'],
            ['Signed',    'Dentist signed; awaiting claims review'],
            ['Valid',     'Approved by claims processor'],
            ['Invalid',   'Rejected by claims processor'],
            ['Returned',  'Sent back to dentist for correction'],
            ['Processed', 'Included in a generated ADC'],
            ['Cancelled', 'Cancelled by dentist or CSR'],
        ] as [$s,$d])
        <tr><td><strong>{{ $s }}</strong></td><td>{{ $d }}</td></tr>
        @endforeach
    </tbody>
</table>

<div class="callout callout-green">
    <div class="callout-title">Service Fee Approval</div>
    Shows clinics with pending fee update requests. On approval, if processed procedures exist for the service, a fee adjustment procedure is automatically created. Navigation badge shows pending count.
</div>

{{-- Claims Management --}}
<div class="section-heading">
    <h2>Claims Management</h2>
    <p>Navigation: Search → Search Claims / Claims Management → Generated ADC</p>
</div>

<div class="two-col">
    <div class="col-left">
        <div class="sub-heading">Search Claims Filters</div>
        <table class="info-table">
            <thead><tr><th style="width:40%">Filter</th><th>Notes</th></tr></thead>
            <tbody>
                @foreach([
                    ['Approval Code',  'Search by specific code'],
                    ['Member Name',    'First or last name'],
                    ['Clinic',         'Required'],
                    ['Claim Status',   'Filter by procedure status'],
                    ['Availment Date', 'Required — date range'],
                ] as [$f,$d])
                <tr><td><strong>{{ $f }}</strong></td><td>{{ $d }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-right">
        <div class="sub-heading">Claim Actions (Signed procedures)</div>
        <table class="info-table">
            <thead><tr><th style="width:25%">Action</th><th>Description</th></tr></thead>
            <tbody>
                @foreach([
                    ['Valid',    'Mark claim as valid — claims.valid'],
                    ['Rejected', 'Reject with reason; returns service quantity — claims.reject'],
                    ['Return',   'Send back to dentist with reason — claims.return'],
                ] as [$a,$d])
                <tr><td><strong>{{ $a }}</strong></td><td>{{ $d }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="callout callout-purple">
    <div class="callout-title">Generating ADC (Print Claims)</div>
    <ol>
        <li>Calculates fees including VAT and EWT per clinic settings</li>
        <li>Creates a GeneratedSoa record</li>
        <li>Generates two PDF copies: Original (printer) and Duplicate (download)</li>
        <li>Marks all included procedures as Processed with an ADC number</li>
        <li>Logs the print activity — ADC format: <code>ADC0000000001</code></li>
    </ol>
</div>

{{-- Search Member Business Rules --}}
<div class="section-heading">
    <h2>Search Member — Business Rules</h2>
    <p>Navigation: Search → Search Member</p>
</div>

<div class="callout callout-red">
    <div class="callout-title">Add Procedure is disabled when:</div>
    <ul>
        <li>Member is not Active</li>
        <li>Member coverage has not started or has expired</li>
        <li>Account is not Active</li>
        <li>Account coverage has not started or has expired</li>
    </ul>
</div>

<div class="sub-heading">Procedure Restrictions</div>
<table class="info-table">
    <tbody>
        @foreach([
            'Special services can only be added by CSR',
            'Member cannot have procedures at different clinics on the same date',
            'Consultation and Simple Tooth Extraction cannot be combined with other procedures on the same date/tooth',
            'Oral Prophylaxis cannot be done with Treatment of Sores or Desensitization on the same date',
            'Temporary and Permanent fillings cannot be on the same tooth on the same date',
            'Extraction can only be done once per tooth; no other services on an extracted tooth',
            'Fixed MBL: total fee must not exceed remaining balance',
            'Procedural MBL: service must have remaining quantity',
        ] as $rule)
        <tr><td>{{ $rule }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- Reports & Imports --}}
<div class="section-heading">
    <h2>Reports &amp; Imports</h2>
    <p>Navigation: Reports → Reports / Imports → Import Logs</p>
</div>

<div class="two-col">
    <div class="col-left">
        <div class="sub-heading">Report Types</div>
        <table class="info-table">
            <thead><tr><th style="width:45%">Report</th><th>Description</th></tr></thead>
            <tbody>
                @foreach([
                    ['Member Status',       'Members with status, type, account, HIP, dates'],
                    ['Dentist List',        'Dentists with clinic, specialization, location'],
                    ['Clinic Accreditation','Clinics with accreditation, VAT, business type'],
                    ['Availment',           'Procedures with member, clinic, service, fee, approval code'],
                    ['Account Status',      'Accounts with plan type, HIP, coverage dates, status'],
                ] as [$r,$d])
                <tr><td><strong>{{ $r }}</strong></td><td>{{ $d }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-right">
        <div class="sub-heading">Import Log Columns</div>
        <table class="info-table">
            <thead><tr><th style="width:40%">Column</th><th>Description</th></tr></thead>
            <tbody>
                @foreach([
                    ['Import Type',   'account, member, or procedure'],
                    ['Batch Status',  'active or deleted'],
                    ['Status',        'processing, partial, failed'],
                    ['Success Rows',  'Successfully imported rows'],
                    ['Skipped Rows',  'Duplicates or skipped'],
                    ['Error Rows',    'Rows that failed validation'],
                ] as [$f,$d])
                <tr><td><strong>{{ $f }}</strong></td><td>{{ $d }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Reference Data --}}
<div class="section-heading">
    <h2>Reference Data (Admin)</h2>
</div>
<table class="info-table">
    <tbody>
        <tr>
            @foreach(['Account Types','Accreditation Statuses','Business Types','HIP','MBL Types','Services'] as $item)
            <td style="width:16%"><span class="badge badge-status">{{ $item }}</span></td>
            @endforeach
        </tr>
        <tr>
            @foreach(['Tax Types','Unit Types','Units','Roles & Permissions','Users','Activity Log'] as $item)
            <td><span class="badge badge-status">{{ $item }}</span></td>
            @endforeach
        </tr>
    </tbody>
</table>

@endsection
