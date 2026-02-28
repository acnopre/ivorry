<?php

namespace Tests\Unit\Imports;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use App\Imports\AccountImport;
use App\Models\ImportLog;

class AccountImportTest extends TestCase
{
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function test_validate_account_row_requires_company_name(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = ['policy_code' => 'POL123'];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'NEW']);

        $this->assertStringContainsString('Required fields', $result);
    }

    public function test_validate_account_row_validates_plan_type(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = [
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'plan_type' => 'INVALID',
            'coverage_type' => 'ACCOUNT',
        ];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'NEW']);

        $this->assertStringContainsString('Invalid plan_type', $result);
    }

    public function test_validate_account_row_validates_coverage_type(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = [
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'plan_type' => 'INDIVIDUAL',
            'coverage_type' => 'INVALID',
        ];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'NEW']);

        $this->assertStringContainsString('Invalid coverage_type', $result);
    }

    public function test_validate_account_row_validates_mbl_type(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = [
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'plan_type' => 'INDIVIDUAL',
            'coverage_type' => 'ACCOUNT',
            'mbl_type' => 'INVALID',
            'effective_date' => '2025-01-01',
            'expiration_date' => '2025-12-31',
        ];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'NEW']);

        $this->assertStringContainsString('Invalid mbl_type', $result);
    }

    public function test_validate_account_row_requires_mbl_amount_for_fixed_type(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = [
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'plan_type' => 'INDIVIDUAL',
            'coverage_type' => 'ACCOUNT',
            'mbl_type' => 'Fixed',
            'effective_date' => '2025-01-01',
            'expiration_date' => '2025-12-31',
        ];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'NEW']);

        $this->assertStringContainsString('MBL amount is required', $result);
    }

    public function test_validate_account_row_validates_endorsement_type(): void
    {
        $log = $this->createMock(ImportLog::class);
        $import = new AccountImport($log);

        $row = ['company_name' => 'Test'];
        $result = $this->invokePrivateMethod($import, 'validateAccountRow', [$row, 'INVALID']);

        $this->assertStringContainsString('Invalid endorsement_type', $result);
    }
}
