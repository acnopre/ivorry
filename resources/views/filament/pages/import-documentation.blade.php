<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                IVORRY Import Documentation
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Complete guide for importing Accounts, Members, and Procedures into the system.
            </p>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="block p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-building-office class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Account Import</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">NEW, RENEWAL, AMENDMENT</p>
                    </div>
                </div>
            </div>

            <div class="block p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-users class="w-8 h-8 text-green-600 dark:text-green-400" />
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Member Import</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Principal & Dependents</p>
                    </div>
                </div>
            </div>

            <div class="block p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Procedure Import</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Historical & Bulk Data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <x-filament::section>
                <x-slot name="heading">Quick Start Guide</x-slot>

                <div class="space-y-4 text-sm">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📋 Import Order</h4>
                        <ol class="list-decimal list-inside space-y-1 text-gray-700 dark:text-gray-300">
                            <li>Import <strong>Accounts</strong> first</li>
                            <li>Then import <strong>Members</strong></li>
                            <li>Finally import <strong>Procedures</strong></li>
                        </ol>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">⚠️ Important Notes</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                            <li>Always backup database before large imports</li>
                            <li>Test with 5-10 rows first</li>
                            <li>Use Migration Mode only for initial data migration</li>
                            <li>Check Import Logs for detailed results</li>
                        </ul>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <h4 class="font-semibold text-green-900 dark:text-green-100 mb-2">✅ File Format</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                            <li>Supported: .xlsx, .xls</li>
                            <li>First row must be column headers</li>
                            <li>Date format: YYYY-MM-DD or Excel date</li>
                            <li>Process in chunks of 500 rows</li>
                        </ul>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="mt-6">
                <x-slot name="heading">Access Locations</x-slot>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Account Import</h4>
                        <p class="text-gray-600 dark:text-gray-400">Accounts → Import XLS button</p>
                    </div>
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Member Import</h4>
                        <p class="text-gray-600 dark:text-gray-400">Members → Import XLS button</p>
                    </div>
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Procedure Import</h4>
                        <p class="text-gray-600 dark:text-gray-400">Import Logs → Import Procedures button</p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
