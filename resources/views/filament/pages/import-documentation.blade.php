<x-filament-panels::page>
    {{-- Quick Start --}}
    <x-filament::section>
        <x-slot name="heading">Quick Start Guide</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 p-4">
                <p class="font-semibold text-primary-800 dark:text-primary-200 mb-2">📋 Import Order</p>
                <ol class="list-decimal list-inside space-y-1 text-gray-700 dark:text-gray-300">
                    <li>Import <strong>Accounts</strong> first</li>
                    <li>Then import <strong>Members</strong></li>
                    <li>Then import <strong>Clinics</strong></li>
                    <li>Finally import <strong>Procedures</strong></li>
                </ol>
            </div>

            <div class="rounded-lg bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 p-4">
                <p class="font-semibold text-success-800 dark:text-success-200 mb-2">✅ File Format</p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                    <li>Supported: <strong>.xlsx</strong>, <strong>.xls</strong></li>
                    <li>First row must be column headers</li>
                    <li>Date format: <code>YYYY-MM-DD</code> or Excel date</li>
                    <li>Processed in chunks of 500 rows</li>
                </ul>
            </div>

            <div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 p-4">
                <p class="font-semibold text-warning-800 dark:text-warning-200 mb-2">⚠️ Important Notes</p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                    <li>Backup database before large imports</li>
                    <li>Test with 5–10 rows first</li>
                    <li>Use <strong>Migration Mode</strong> only for initial data migration</li>
                    <li>Check Import Logs for row-level results</li>
                </ul>
            </div>
        </div>
    </x-filament::section>

    {{-- Access Locations --}}
    <x-filament::section>
        <x-slot name="heading">Access Locations</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @foreach([
                ['icon' => 'heroicon-o-building-office',          'color' => 'text-primary-600 dark:text-primary-400',  'title' => 'Account Import',   'path' => 'Accounts & Members → Accounts → Import XLS'],
                ['icon' => 'heroicon-o-users',                    'color' => 'text-success-600 dark:text-success-400',  'title' => 'Member Import',    'path' => 'Accounts & Members → Members → Import XLS'],
                ['icon' => 'heroicon-o-building-storefront',      'color' => 'text-warning-600 dark:text-warning-400',  'title' => 'Clinic Import',    'path' => 'Dental Management → Clinic Details → Import XLS'],
                ['icon' => 'heroicon-o-clipboard-document-list',  'color' => 'text-purple-600 dark:text-purple-400',    'title' => 'Procedure Import', 'path' => 'Imports → Import Logs → Import Procedures'],
            ] as $item)
            <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <x-dynamic-component :component="$item['icon']" class="w-6 h-6 mt-0.5 shrink-0 {{ $item['color'] }}" />
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item['title'] }}</p>
                    <p class="text-gray-500 dark:text-gray-400">{{ $item['path'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Account Import --}}
    <x-filament::section>
        <x-slot name="heading">Account Import</x-slot>
        <x-slot name="description">Navigation: Accounts & Members → Accounts → Import XLS</x-slot>

        @include('filament.pages.import-documentation.table', [
            'rows' => [
                ['company_name',          true,  'Name of the corporate client'],
                ['policy_code',           true,  'Unique policy reference code'],
                ['hip',                   false, 'Health Insurance Provider name'],
                ['card_used',             false, 'Card type used (e.g. IVORRY, HMO)'],
                ['effective_date',        true,  'Coverage start date (YYYY-MM-DD)'],
                ['expiration_date',       false, 'Auto-calculated if blank (1 year − 1 day)'],
                ['plan_type',             true,  'INDIVIDUAL or SHARED'],
                ['coverage_type',         true,  'ACCOUNT or MEMBER'],
                ['account_coverage_type', false, 'DEFAULT, ALL_PRINCIPAL, or ALL_DEPENDENT (INDIVIDUAL plan only)'],
                ['endorsement_type',      false, 'NEW (default), RENEWAL, or AMENDMENT'],
                ['mbl_type',              true,  'Procedural or Fixed'],
                ['mbl_amount',            false, 'Required when mbl_type is Fixed'],
                ['consultation',                                                false, 'unlimited or numeric quantity'],
                ['treatment_of_sores_blisters',                                false, 'unlimited or numeric quantity'],
                ['temporary_fillings',                                         false, 'unlimited or numeric quantity'],
                ['simple_tooth_extraction',                                    false, 'unlimited or numeric quantity'],
                ['recementation_of_fixed_bridges_crowns_jackets_inlays_onlays',false, 'unlimited or numeric quantity'],
                ['adjustment_of_dentures',                                     false, 'unlimited or numeric quantity'],
                ['oral_prophylaxis',                                           false, 'Numeric quantity'],
                ['permanent_filling_per_tooth',                                false, 'Numeric quantity'],
                ['permanent_filling_per_surface',                              false, 'Numeric quantity'],
                ['desensitization_of_hypersensitive_teeth',                    false, 'Numeric quantity'],
                ['fluoride_brushing',                                          false, 'Numeric quantity'],
                ['incision_and_drainage',                                      false, 'Numeric quantity'],
                ['peri_apical_xray',                                           false, 'Numeric quantity'],
                ['panoramic_xray',                                             false, 'Numeric quantity'],
                ['complicated_difficult_extraction',                           false, 'Numeric quantity'],
                ['odontectomy_removal_of_impacted_tooth',                      false, 'Numeric quantity'],
                ['root_canal_treatment_per_canal',                             false, 'Numeric quantity'],
                ['root_canal_treatment_per_tooth',                             false, 'Numeric quantity'],
                ['jacket_crowns',                                              false, 'Numeric quantity'],
                ['dentures',                                                   false, 'Numeric quantity'],
                ['pit_and_fissure_sealants',                                   false, 'Numeric quantity'],
                ['topical_fluoride_application',                               false, 'Numeric quantity'],
                ['minor_soft_tissue_surgery',                                  false, 'Numeric quantity'],
            ]
        ])
    </x-filament::section>

    {{-- Member Import --}}
    <x-filament::section>
        <x-slot name="heading">Member Import</x-slot>
        <x-slot name="description">Navigation: Accounts & Members → Members → Import XLS</x-slot>

        @include('filament.pages.import-documentation.table', [
            'rows' => [
                ['account_name',              true,  'Must match an existing account company name'],
                ['first_name',                true,  'Member first name'],
                ['last_name',                 true,  'Member last name'],
                ['middle_name',               false, 'Member middle name'],
                ['suffix',                    false, 'e.g. Jr., Sr.'],
                ['member_type',               true,  'PRINCIPAL or DEPENDENT'],
                ['card_number',               true,  'Unique card number (letters and numbers only)'],
                ['old_card_number',           false, 'Existing card number — used to find and update the member, new card_number will replace it'],
                ['birthdate',                 false, 'Date of birth (YYYY-MM-DD)'],
                ['gender',                    false, 'M, F, male, or female'],
                ['email',                     false, 'Email address'],
                ['phone',                     false, 'Mobile number'],
                ['address',                   false, 'Home address'],
                ['status',                    true,  'ACTIVE or INACTIVE'],
                ['inactive_date',             false, 'Date the member became inactive (YYYY-MM-DD). Sets status to INACTIVE automatically.'],
                ['endorsement_deletion_date', true,  'Required when status is INACTIVE. Scheduled endorsement end/deletion date (YYYY-MM-DD)'],
                ['effective_date',            false, 'Required when account Coverage Period Type is MEMBER'],
                ['expiration_date',           false, 'Required when account Coverage Period Type is MEMBER'],
            ]
        ])
    </x-filament::section>

    {{-- Clinic Import --}}
    <x-filament::section>
        <x-slot name="heading">Clinic Import</x-slot>
        <x-slot name="description">Navigation: Dental Management → Clinic Details → Import XLS</x-slot>

        @include('filament.pages.import-documentation.table', [
            'rows' => [
                ['clinic_name',          true,  'Name on signage'],
                ['registered_name',      false, 'BIR registered name'],
                ['clinic_email',         false, 'Email address'],
                ['password',             false, 'Login password for clinic portal'],
                ['clinic_mobile',        false, 'Mobile number'],
                ['clinic_landline',      false, 'Landline number'],
                ['complete_address',     false, 'Full address'],
                ['street',               false, 'Street address'],
                ['region_name',          false, 'Region name'],
                ['province_name',        false, 'Province name'],
                ['municipality_name',    false, 'City or municipality name'],
                ['barangay_name',        false, 'Barangay name'],
                ['business_type',        false, 'SOLE PROPRIETORSHIP, PARTNERSHIP, or CORPORATION'],
                ['vat_type',             false, 'VAT 12%, VAT ZERO, VAT EXEMPT, or NON-VAT'],
                ['withholding_tax',      false, 'ZERO, 2%, 5%, or 10%'],
                ['tax_identification_no',false, 'Tax Identification Number'],
                ['sec_registration_no',  false, 'SEC registration number'],
                ['ptr_no',               false, 'PTR number'],
                ['ptr_date_issued',      false, 'PTR date issued (YYYY-MM-DD)'],
                ['accreditation_status', false, 'ACTIVE, INACTIVE, SILENT, SPECIFIC ACCOUNT, or SPECIFIC HIP'],
                ['account_name',         false, 'Required when accreditation_status is SPECIFIC ACCOUNT'],
                ['hip_name',             false, 'Required when accreditation_status is SPECIFIC HIP'],
                ['is_branch',            false, '0 or 1'],
                ['bank_name',            false, 'Bank name'],
                ['bank_branch',          false, 'Bank branch'],
                ['bank_account_name',    false, 'Account name on bank'],
                ['bank_account_number',  false, 'Bank account number'],
                ['account_type',         false, 'SAVINGS or CURRENT'],
                ['owner_first_name',     false, 'Owner first name'],
                ['owner_last_name',      false, 'Owner last name'],
                ['owner_middle_initial', false, 'Owner middle initial'],
                ['owner_prc_license',    false, 'PRC license number'],
                ['owner_prc_expiration', false, 'PRC expiration date (YYYY-MM-DD)'],
                ['clinic_staff_name',    false, 'Staff contact name'],
                ['clinic_staff_mobile',  false, 'Staff mobile number'],
                ['clinic_staff_viber',   false, 'Staff Viber number'],
                ['clinic_staff_email',   false, 'Staff email address'],
                ['viber_no',             false, 'Clinic Viber number'],
                ['alt_address',          false, 'Alternative address'],
                ['remarks',              false, 'Additional notes'],
                ['consultation',                                                false, 'Service fee'],
                ['treatment_of_sores_blisters',                                false, 'Service fee'],
                ['temporary_fillings',                                         false, 'Service fee'],
                ['simple_tooth_extraction',                                    false, 'Service fee'],
                ['recementation_of_fixed_bridges_crowns_jackets_inlays_onlays',false, 'Service fee'],
                ['adjustment_of_dentures',                                     false, 'Service fee'],
                ['oral_prophylaxis',                                           false, 'Service fee'],
                ['permanent_filling_per_tooth',                                false, 'Service fee'],
                ['permanent_filling_per_surface',                              false, 'Service fee'],
                ['desensitization_of_hypersensitive_teeth',                    false, 'Service fee'],
                ['fluoride_brushing',                                          false, 'Service fee'],
                ['incision_and_drainage',                                      false, 'Service fee'],
                ['peri_apical_xray',                                           false, 'Service fee'],
                ['panoramic_xray',                                             false, 'Service fee'],
                ['complicated_difficult_extraction',                           false, 'Service fee'],
                ['odontectomy_removal_of_impacted_tooth',                      false, 'Service fee'],
                ['root_canal_treatment_per_canal',                             false, 'Service fee'],
                ['root_canal_treatment_per_tooth',                             false, 'Service fee'],
                ['jacket_crowns',                                              false, 'Service fee'],
                ['dentures',                                                   false, 'Service fee'],
                ['pit_and_fissure_sealants',                                   false, 'Service fee'],
                ['topical_fluoride_application',                               false, 'Service fee'],
                ['minor_soft_tissue_surgery',                                  false, 'Service fee'],
            ]
        ])
    </x-filament::section>

    {{-- Procedure Import --}}
    <x-filament::section>
        <x-slot name="heading">Procedure Import</x-slot>
        <x-slot name="description">Navigation: Imports → Import Logs → Import Procedures</x-slot>

        @include('filament.pages.import-documentation.table', [
            'rows' => [
                ['card_number',    true,  'Member card number'],
                ['clinic_name',    true,  'Must match an existing accredited clinic'],
                ['service_name',   true,  'Must match an existing service name'],
                ['availment_date', true,  'Date of procedure (YYYY-MM-DD)'],
                ['quantity',       false, 'Number of units (default: 1)'],
                ['applied_fee',    false, 'Fee applied (required for Fixed MBL accounts)'],
                ['approval_code',  false, 'Existing approval code (auto-generated if blank)'],
                ['adc_number',     false, 'ADC reference number for migrated records'],
                ['status',         false, 'pending, signed, valid, processed (default: processed for migration)'],
            ]
        ])
    </x-filament::section>

    {{-- Import Logs --}}
    <x-filament::section>
        <x-slot name="heading">Import Logs</x-slot>
        <x-slot name="description">Navigation: Imports → Import Logs</x-slot>

        <div class="space-y-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left">
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300 w-1/4">Column</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach([
                        ['Filename',     'Original uploaded file name'],
                        ['Import Type',  'account, member, clinic, or procedure'],
                        ['Status',       'processing, partial, failed, or completed'],
                        ['Total Rows',   'Total rows in the uploaded file'],
                        ['Success Rows', 'Rows successfully imported'],
                        ['Error Rows',   'Rows that failed validation'],
                        ['Imported By',  'User who performed the import'],
                        ['Created At',   'Import timestamp'],
                    ] as [$col, $desc])
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-200">{{ $col }}</td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $desc }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 p-4 text-sm">
                <p class="font-semibold text-primary-800 dark:text-primary-200 mb-2">Import Log Actions</p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                    <li><strong>View Details</strong> — See row-level results with error messages per row</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
