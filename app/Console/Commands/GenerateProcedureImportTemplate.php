<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Service;
use App\Models\Clinic;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class GenerateProcedureImportTemplate extends Command
{
    protected $signature = 'generate:procedure-template {--rows=10}';
    protected $description = 'Generate sample procedure import XLS template with test data';

    public function handle()
    {
        $rows = (int) $this->option('rows');
        
        $members = Member::with('account')->limit($rows)->get();
        $services = Service::all();
        $clinics = Clinic::all();

        $data = [
            ['first_name', 'last_name', 'card_number', 'service_name', 'clinic_name', 'availment_date', 'quantity', 'applied_fee', 'remarks']
        ];

        if ($members->isEmpty() || $services->isEmpty() || $clinics->isEmpty()) {
            $this->warn('No data found in database. Generating blank template...');
            
            // Generate blank template with sample data
            for ($i = 1; $i <= $rows; $i++) {
                $data[] = [
                    'John',
                    'Doe',
                    'CARD-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'Oral Prophylaxis',
                    'Sample Dental Clinic',
                    now()->subDays($i)->format('Y-m-d'),
                    1,
                    500,
                    'Sample data'
                ];
            }
        } else {
            foreach ($members as $index => $member) {
                $service = $services->random();
                $clinic = $clinics->random();
                
                $data[] = [
                    $member->first_name,
                    $member->last_name,
                    $member->card_number,
                    $service->name,
                    $clinic->clinic_name,
                    now()->subDays(rand(1, 30))->format('Y-m-d'),
                    rand(1, 3),
                    rand(100, 1000),
                    'Test import data'
                ];
            }
        }

        $filename = 'procedure_import_template_' . now()->format('YmdHis') . '.xlsx';
        $path = 'imports/' . $filename;

        Excel::store(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            public function __construct(private array $data) {}
            public function array(): array { return $this->data; }
        }, $path, 'public');

        $fullPath = Storage::disk('public')->path($path);
        
        $this->info("✅ Generated: {$fullPath}");
        $this->info("📊 Rows: " . (count($data) - 1));
        $this->newLine();
        $this->info('Columns: first_name, last_name, card_number, service_name, clinic_name, availment_date, quantity, applied_fee, remarks');
        
        return 0;
    }
}
