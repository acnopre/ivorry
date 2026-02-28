<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Member;
use App\Models\Procedure;
use App\Models\User;
use App\Services\MblBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MblBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_mbl_type_change_from_procedural_to_fixed_calculates_balance(): void
    {
        $account = Account::create([
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'mbl_type' => 'Procedural',
            'created_by' => User::factory()->create()->id,
        ]);

        $member = Member::create([
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'card_number' => 'CARD001',
        ]);

        MblBalanceService::handleMblTypeChange(
            $account->id,
            'Procedural',
            'Fixed',
            5000.00,
            now()->format('Y-m-d')
        );

        $this->assertEquals(5000.00, $member->fresh()->mbl_balance);
    }

    public function test_handle_mbl_type_change_from_fixed_to_procedural_removes_balance(): void
    {
        $account = Account::create([
            'company_name' => 'Test',
            'policy_code' => 'POL123',
            'hip' => 'HIP001',
            'mbl_type' => 'Fixed',
            'created_by' => User::factory()->create()->id,
        ]);

        $member = Member::create([
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'card_number' => 'CARD001',
            'mbl_balance' => 3000.00,
        ]);

        MblBalanceService::handleMblTypeChange(
            $account->id,
            'Fixed',
            'Procedural',
            null,
            now()->format('Y-m-d')
        );

        $this->assertNull($member->fresh()->mbl_balance);
    }
}
