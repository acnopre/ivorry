<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">HPDAI System Documentation</h2>
            <p class="text-gray-500 dark:text-gray-400">Healthcare Plan Dental Administration Interface — Complete User Guide</p>
        </div>

        {{-- Quick Nav --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach([
                ['icon' => 'heroicon-o-building-office-2', 'label' => 'Accounts & Members', 'color' => 'blue', 'href' => '#accounts'],
                ['icon' => 'heroicon-o-heart', 'label' => 'Dental Management', 'color' => 'green', 'href' => '#dental'],
                ['icon' => 'heroicon-o-clipboard-document-check', 'label' => 'Claims Management', 'color' => 'purple', 'href' => '#claims'],
                ['icon' => 'heroicon-o-chart-bar', 'label' => 'Reports & Imports', 'color' => 'orange', 'href' => '#reports'],
            ] as $nav)
            <a href="{{ $nav['href'] }}" class="flex items-center gap-3 p-4 bg-{{ $nav['color'] }}-50 dark:bg-{{ $nav['color'] }}-900/20 rounded-lg hover:bg-{{ $nav['color'] }}-100 dark:hover:bg-{{ $nav['color'] }}-900/30 transition">
                <x-dynamic-component :component="$nav['icon']" class="w-6 h-6 text-{{ $nav['color'] }}-600 dark:text-{{ $nav['color'] }}-400 shrink-0" />
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $nav['label'] }}</span>
            </a>
            @endforeach
        </div>

        {{-- User Roles --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-primary-500" /> User Roles & Permissions
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Role</th>
                            <th class="text-left py-2 font-semibold text-gray-700 dark:text-gray-300">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach([
                            ['Super Admin', 'Full system access'],
                            ['Upper Management', 'Approvals, reports, dashboard overview'],
                            ['Middle Management', 'Account approvals'],
                            ['Account Manager', 'Manage accounts and members'],
                            ['Claims Processor', 'Process and print ADC claims'],
                            ['Accreditation', 'Manage clinic accreditation'],
                            ['CSR', 'Search members, add procedures, sign claims'],
                            ['Dentist', 'View own procedures, sign procedures'],
                            ['Member', 'View own profile and benefits'],
                        ] as [$role, $desc])
                        <tr>
                            <td class="py-2 pr-4 font-medium text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $role }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $desc }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Accounts & Members --}}
        <div id="accounts" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="w-5 h-5 text-blue-500" /> Accounts & Members
            </h3>

            {{-- Accounts --}}
            <div>
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Accounts</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Navigation: <span class="font-medium text-gray-700 dark:text-gray-300">Accounts & Members → Accounts</span></p>

                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account Information Fields</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead><tr class="border-b dark:border-gray-700"><th class="text-left py-1 pr-3 text-gray-600 dark:text-gray-400">Field</th><th class="text-left py-1 text-gray-600 dark:text-gray-400">Description</th></tr></thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    @foreach([
                                        ['Company Name', 'Name of the corporate client'],
                                        ['Policy Code', 'Unique identifier for the policy'],
                                        ['HIP', 'Health Insurance Provider'],
                                        ['Plan Type', 'Individual — own MBL; Shared — shared pool'],
                                        ['Coverage Period Type', 'Account — shared dates; Member — individual dates'],
                                        ['MBL Type', 'Procedural — by quantity; Fixed — by peso amount'],
                                        ['MBL Amount', 'Required only when MBL Type is Fixed'],
                                    ] as [$f, $d])
                                    <tr><td class="py-1 pr-3 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $f }}</td><td class="py-1 text-gray-500 dark:text-gray-400">{{ $d }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account Statuses</p>
                        <div class="space-y-1">
                            @foreach([
                                ['Pending', 'Awaiting approval', 'yellow'],
                                ['Approved', 'Account is active and usable', 'green'],
                                ['Rejected', 'Account was not approved', 'red'],
                                ['Active', 'Within coverage period', 'green'],
                                ['Inactive', 'Not yet active', 'gray'],
                                ['Expired', 'Coverage has ended', 'red'],
                            ] as [$s, $d, $c])
                            <div class="flex items-center gap-2 text-sm">
                                <span class="inline-block w-2 h-2 rounded-full bg-{{ $c }}-500 shrink-0"></span>
                                <span class="font-medium text-gray-700 dark:text-gray-300 w-20">{{ $s }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $d }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-sm">
                    <p class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Endorsement Types</p>
                    <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                        <li><span class="font-medium">NEW</span> — Initial account creation</li>
                        <li><span class="font-medium">RENEWAL</span> — Extends account for another period. Resets service quantities. Effective date must differ from current.</li>
                        <li><span class="font-medium">AMENDMENT</span> — Modifies details, services, or members without changing the period</li>
                    </ul>
                </div>
            </div>

            <hr class="dark:border-gray-700">

            {{-- Members --}}
            <div>
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Members</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Navigation: <span class="font-medium text-gray-700 dark:text-gray-300">Accounts & Members → Members</span></p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b dark:border-gray-700"><th class="text-left py-1 pr-3 text-gray-600 dark:text-gray-400">Field</th><th class="text-left py-1 text-gray-600 dark:text-gray-400">Description</th></tr></thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach([
                                ['Account', 'Select an active account'],
                                ['Card Number', 'Unique member card number'],
                                ['COC Number', 'Alternative to card number (toggle "Enable COC Number")'],
                                ['Member Type', 'Principal or Dependent'],
                                ['Status', 'Active or Inactive'],
                                ['Coverage Dates', 'Visible only when account Coverage Period Type is Member'],
                            ] as [$f, $d])
                            <tr><td class="py-1 pr-3 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $f }}</td><td class="py-1 text-gray-500 dark:text-gray-400">{{ $d }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Dental Management --}}
        <div id="dental" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-heart class="w-5 h-5 text-green-500" /> Dental Management
            </h3>

            {{-- Procedure Statuses --}}
            <div>
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Procedure Statuses</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach([
                        ['Pending', 'Submitted, awaiting dentist signature', 'yellow'],
                        ['Signed', 'Dentist signed; awaiting claims review', 'blue'],
                        ['Valid', 'Approved by claims processor', 'green'],
                        ['Invalid', 'Rejected by claims processor', 'red'],
                        ['Returned', 'Sent back to dentist for correction', 'orange'],
                        ['Processed', 'Included in a generated ADC', 'purple'],
                        ['Cancelled', 'Cancelled by dentist or CSR', 'gray'],
                    ] as [$s, $d, $c])
                    <div class="border dark:border-gray-700 rounded-lg p-3 text-sm">
                        <span class="inline-flex items-center gap-1 font-medium text-{{ $c }}-600 dark:text-{{ $c }}-400 mb-1">
                            <span class="w-2 h-2 rounded-full bg-{{ $c }}-500"></span>{{ $s }}
                        </span>
                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $d }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Service Fee Approval --}}
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-sm">
                <p class="font-semibold text-green-800 dark:text-green-200 mb-1">Service Fee Approval</p>
                <p class="text-gray-700 dark:text-gray-300">Navigation: <span class="font-medium">Dental Management → Service Fee Approval</span></p>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Shows clinics with pending fee update requests. On approval, if processed procedures exist for the service, a fee adjustment procedure is automatically created. Navigation badge shows pending count.</p>
            </div>
        </div>

        {{-- Claims Management --}}
        <div id="claims" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-purple-500" /> Claims Management
            </h3>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Search Claims Filters</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b dark:border-gray-700"><th class="text-left py-1 pr-3 text-gray-600 dark:text-gray-400">Filter</th><th class="text-left py-1 text-gray-600 dark:text-gray-400">Notes</th></tr></thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                @foreach([
                                    ['Approval Code', 'Search by specific code'],
                                    ['Member Name', 'First or last name'],
                                    ['Clinic', 'Required'],
                                    ['Claim Status', 'Filter by procedure status'],
                                    ['Availment Date', 'Required — date range'],
                                ] as [$f, $d])
                                <tr><td class="py-1 pr-3 font-medium text-gray-700 dark:text-gray-300">{{ $f }}</td><td class="py-1 text-gray-500 dark:text-gray-400">{{ $d }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Claim Actions (Signed procedures)</h4>
                    <div class="space-y-2 text-sm">
                        @foreach([
                            ['Valid', 'Mark claim as valid', 'green', 'claims.valid'],
                            ['Rejected', 'Reject with reason; returns service quantity', 'red', 'claims.reject'],
                            ['Return', 'Send back to dentist with reason', 'yellow', 'claims.return'],
                        ] as [$a, $d, $c, $p])
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 inline-block px-2 py-0.5 rounded text-xs font-medium bg-{{ $c }}-100 dark:bg-{{ $c }}-900/30 text-{{ $c }}-700 dark:text-{{ $c }}-300 shrink-0">{{ $a }}</span>
                            <div>
                                <p class="text-gray-700 dark:text-gray-300">{{ $d }}</p>
                                <p class="text-xs text-gray-400">Permission: {{ $p }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-sm">
                <p class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Generating ADC (Print Claims)</p>
                <ol class="list-decimal list-inside space-y-1 text-gray-700 dark:text-gray-300">
                    <li>Calculates fees including VAT and EWT per clinic settings</li>
                    <li>Creates a GeneratedSoa record</li>
                    <li>Generates two PDF copies: <strong>Original</strong> (printer) and <strong>Duplicate</strong> (download)</li>
                    <li>Marks all included procedures as <strong>Processed</strong> with an ADC number</li>
                    <li>Logs the print activity</li>
                </ol>
                <p class="mt-2 text-gray-500 dark:text-gray-400">ADC sequence format: <code class="bg-purple-100 dark:bg-purple-900/40 px-1 rounded">ADC0000000001</code></p>
            </div>
        </div>

        {{-- Search Member Business Rules --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-yellow-500" /> Search Member — Business Rules
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">The <strong>Add Procedure</strong> button is disabled if any of these conditions apply:</p>
            <div class="grid md:grid-cols-2 gap-3 text-sm">
                @foreach([
                    ['Member is not Active', 'red'],
                    ['Member coverage has not started or has expired', 'red'],
                    ['Account is not Active', 'red'],
                    ['Account coverage has not started or has expired', 'red'],
                ] as [$rule, $c])
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <x-heroicon-o-x-circle class="w-4 h-4 text-{{ $c }}-500 shrink-0" />{{ $rule }}
                </div>
                @endforeach
            </div>

            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2">Procedure restrictions enforced:</p>
            <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                <li>Special services can only be added by CSR</li>
                <li>Member cannot have procedures at different clinics on the same date</li>
                <li>Consultation and Simple Tooth Extraction cannot be combined with other procedures on the same date/tooth</li>
                <li>Oral Prophylaxis cannot be done with Treatment of Sores or Desensitization on the same date</li>
                <li>Temporary and Permanent fillings cannot be on the same tooth on the same date</li>
                <li>Extraction can only be done once per tooth; no other services on an extracted tooth</li>
                <li>Fixed MBL: total fee must not exceed remaining balance</li>
                <li>Procedural MBL: service must have remaining quantity</li>
            </ul>
        </div>

        {{-- Reports & Imports --}}
        <div id="reports" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-orange-500" /> Reports & Imports
            </h3>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Report Types</h4>
                    <div class="space-y-2 text-sm">
                        @foreach([
                            ['Member Status Report', 'Members with status, type, account, HIP, dates'],
                            ['Dentist List Report', 'Dentists with clinic, specialization, location'],
                            ['Clinic Accreditation Report', 'Clinics with accreditation, VAT, business type'],
                            ['Availment Report', 'Procedures with member, clinic, service, fee, approval code'],
                            ['Account Status Report', 'Accounts with plan type, HIP, coverage dates, status'],
                        ] as [$r, $d])
                        <div class="border dark:border-gray-700 rounded p-2">
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ $r }}</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $d }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Import Log Statuses</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b dark:border-gray-700"><th class="text-left py-1 pr-3 text-gray-600 dark:text-gray-400">Column</th><th class="text-left py-1 text-gray-600 dark:text-gray-400">Description</th></tr></thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                @foreach([
                                    ['Import Type', 'account, member, or procedure'],
                                    ['Batch Status', 'active or deleted'],
                                    ['Status', 'processing, partial, failed'],
                                    ['Success Rows', 'Successfully imported rows'],
                                    ['Skipped Rows', 'Duplicates or skipped'],
                                    ['Error Rows', 'Rows that failed validation'],
                                ] as [$f, $d])
                                <tr><td class="py-1 pr-3 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $f }}</td><td class="py-1 text-gray-500 dark:text-gray-400">{{ $d }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reference Data --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-circle-stack class="w-5 h-5 text-gray-500" /> Reference Data (Admin)
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                @foreach([
                    'Account Types', 'Accreditation Statuses', 'Business Types', 'HIP',
                    'MBL Types', 'Services', 'Tax Types', 'Unit Types',
                    'Units', 'Roles & Permissions', 'Users', 'Activity Log',
                ] as $item)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 border dark:border-gray-700 rounded p-2">
                    <x-heroicon-o-tag class="w-3.5 h-3.5 shrink-0" />{{ $item }}
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
